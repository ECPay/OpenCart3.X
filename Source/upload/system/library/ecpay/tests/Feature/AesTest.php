<?php
namespace Ecpay\Sdk;

use Ecpay\Sdk\Services\AesService;
use Ecpay\Sdk\TestCase\SingleServiceTestCase;

final class AesTest extends SingleServiceTestCase
{
    /**
     * 取得測試 Service
     *
     * @return mixed
     */
    protected function getService()
    {
        return $this->factory->create(AesService::class);
    }

    public function testEncrypt()
    {
        // 輸入
        $input = [
            'MerchantID' => 'MID',
        ];

        // 預期結果
        $expected = '718TmA7uA7OIgEUfO/ivJuRKnYvjfQwyXjKDpxowb8toWx9rH/J/xPuQ+xd7HWSj';

        // 執行
        $actual = $this->service->encrypt($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }

    public function testEncryptData()
    {
        // 輸入
        $input = [
            'MerchantID' => 'MID',
            'Data' => [
                'MerchantID' => 'MID',
            ]
        ];

        // 預期結果
        $expected = [
            'MerchantID' => 'MID',
            'Data' => '718TmA7uA7OIgEUfO/ivJuRKnYvjfQwyXjKDpxowb8toWx9rH/J/xPuQ+xd7HWSj',
        ];

        // 執行
        $actual = $this->service->encryptData($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }

    public function testDecrypt()
    {
        // 輸入
        $input = '718TmA7uA7OIgEUfO/ivJuRKnYvjfQwyXjKDpxowb8toWx9rH/J/xPuQ+xd7HWSj';

        // 預期結果
        $expected = [
            'MerchantID' => 'MID',
        ];

        // 執行
        $actual = $this->service->decrypt($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }

    public function testDecryptData()
    {
        // 輸入
        $input = [
            'MerchantID' => 'MID',
            'Data' => '718TmA7uA7OIgEUfO/ivJuRKnYvjfQwyXjKDpxowb8toWx9rH/J/xPuQ+xd7HWSj',
        ];

        // 預期結果
        $expected = [
            'MerchantID' => 'MID',
            'Data' => [
                'MerchantID' => 'MID',
            ]
        ];

        // 執行
        $actual = $this->service->decryptData($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
}
