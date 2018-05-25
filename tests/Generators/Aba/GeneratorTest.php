<?php
declare(strict_types=1);

namespace Tests\EoneoPay\BankFiles\Generators\Aba;

use EoneoPay\BankFiles\Generators\Aba\Generator;
use EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException;
use EoneoPay\BankFiles\Generators\Exceptions\LengthMismatchesException;
use EoneoPay\BankFiles\Generators\Exceptions\ValidationFailedException;
use Tests\EoneoPay\BankFiles\Generators\Aba\TestCase as AbaTestCase;

class GeneratorTest extends AbaTestCase
{
    /**
     * Generator should throw exception when required attributes not set.
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testAttributesWithDefinedRuleAreRequiredException(): void
    {
        $this->expectException(ValidationFailedException::class);

        $transaction = $this->createTransaction();
        $transaction->setAttribute('transactionCode', null);

        (new Generator($this->createDescriptiveRecord(), [$transaction]))->getContents();
    }

    /**
     * Generator should throw exception when no transactions given.
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testEmptyTransactionsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Generator($this->createDescriptiveRecord(), []);
    }

    /**
     * Generator should throw exception when invalid transaction given.
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testInvalidTransactionException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Generator($this->createDescriptiveRecord(), ['invalid']))->getContents();
    }

    /**
     * Should return contents as string with descriptive record in it
     *
     * @group Generator-Aba
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testShouldReturnContents(): void
    {
        $descriptiveRecord = $this->createDescriptiveRecord();
        $generator = new Generator($descriptiveRecord, [$this->createTransaction()]);

        self::assertNotEmpty($generator->getContents());
        self::assertContains($descriptiveRecord->getAttributesAsLine(), $generator->getContents());
    }

    /**
     * Should trow exception if DescriptiveRecord's length is greater than 120
     *
     * @group Generator-Aba
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testShouldThrowExceptionIfDescriptiveRecordLineExceeds(): void
    {
        $this->expectException(LengthMismatchesException::class);

        $descriptiveRecord = $this->createDescriptiveRecord();
        $descriptiveRecord->setAttribute('nameOfUserSupplyingFile', \str_pad('', 41));

        (new Generator($descriptiveRecord, [$this->createTransaction()]))->getContents();
    }

    /**
     * Should trow exception if transaction length is greater than 120
     *
     * @group Generator-Aba
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testShouldThrowExceptionIfTransactionLineExceeds(): void
    {
        $this->expectException(LengthMismatchesException::class);

        $transaction = $this->createTransaction();
        $transaction->setAttribute('amount', '00000012555');

        (new Generator($this->createDescriptiveRecord(), [$transaction]))->getContents();
    }

    /**
     * Should throw exception if validation fails
     *
     * @group Generator-Aba
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testShouldThrowExceptionIfValidationFails(): void
    {
        $this->expectException(ValidationFailedException::class);

        $descriptiveRecord = $this->createDescriptiveRecord();
        $descriptiveRecord
            ->setAttribute('numberOfUserSupplyingFile', '49262x')
            ->setAttribute('dateToBeProcessed', '10081Q');

        (new Generator($descriptiveRecord, [$this->createTransaction()]))->getContents();
    }

    /**
     * Should thrown validation exception if BSB format is invalid
     *
     * @group Generator-Aba
     *
     * @return void
     *
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testShouldThrowValidationExceptionIfWrongBSBFormat(): void
    {
        $expected = [
            'attribute' => 'bsbNumber',
            'value' => '1112333',
            'rule' => 'bsb'
        ];

        $trans = $this->createTransaction();
        // without '-'
        $trans->setAttribute('bsbNumber', '1112333');

        try {
            (new Generator($this->createDescriptiveRecord(), [$trans]))->getContents();
        } /** @noinspection PhpRedundantCatchClause Inspection */ catch (ValidationFailedException $exception) {
            self::assertEquals($expected, $exception->getErrors()[0]);
        }

        $this->expectException(ValidationFailedException::class);

        $trans->setAttribute('bsbNumber', '111--33');
        (new Generator($this->createDescriptiveRecord(), [$trans]))->getContents();
    }

    /**
     * Descriptive record, transactions and file total record should be present on the contents
     *
     * @group Generator-Aba
     *
     * @return void
     * @throws \EoneoPay\BankFiles\Generators\Exceptions\InvalidArgumentException
     */
    public function testValuesShouldBePresentInTheContent(): void
    {
        $descriptiveRecord = $this->createDescriptiveRecord();

        $transactions[] = $this->createTransaction();
        $transactions[] = $this->createTransaction();
        /** @var \EoneoPay\BankFiles\Generators\Aba\Objects\Transaction $trans */
        $trans = $transactions[0];

        $fileTotalRecord = $this->createFileTotalRecord();

        $generator = new Generator($descriptiveRecord, $transactions, $fileTotalRecord);

        self::assertContains($descriptiveRecord->getAttributesAsLine(), $generator->getContents());
        self::assertContains($trans->getAttributesAsLine(), $generator->getContents());
        self::assertContains($fileTotalRecord->getAttributesAsLine(), $generator->getContents());
    }
}
