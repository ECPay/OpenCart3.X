<?php
namespace Ecpay\Sdk;

use Ecpay\Sdk\Services\CheckMacValueService;
use Ecpay\Sdk\TestCase\SingleServiceTestCase;

final class Sha256CheckMacValueTest extends SingleServiceTestCase
{
    /**
     * 取得 Factory 選項
     *
     * @return array
     */
    protected function getFactoryOptions()
    {
        $options = parent::getFactoryOptions();
        $options['hashMethod'] = CheckMacValueService::METHOD_SHA256;

        return $options;
    }

    /**
     * 取得測試 Service
     *
     * @return mixed
     */
    protected function getService()
    {
        return $this->factory->create(CheckMacValueService::class);
    }
    
    public function testAppend()
    {
        // 輸入
        $input = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'Test201102145704',
            'MerchantTradeDate' => '2020/10/20 14:57:04',
            'PaymentType' => 'aio',
            'TotalAmount' => 100,
            'TradeDesc' => '檢查碼建立測試',
            'ItemName' => '測試商品 100 TWD x 1',
            'ReturnURL' => 'https://www.ecpay.com.tw/receive.php',
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
        ];

        // 預期結果
        $expected = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'Test201102145704',
            'MerchantTradeDate' => '2020/10/20 14:57:04',
            'PaymentType' => 'aio',
            'TotalAmount' => 100,
            'TradeDesc' => '檢查碼建立測試',
            'ItemName' => '測試商品 100 TWD x 1',
            'ReturnURL' => 'https://www.ecpay.com.tw/receive.php',
            'ChoosePayment' => 'ALL',
            'CheckMacValue' => '5DC5C11B7E9D42A5D750511E6BE08CDE759104F7EAB6CDD761AE120A5A8ABB34',
            'EncryptType' => 1,
        ];

        // 執行
        $actual = $this->service->append($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }

    public function testGenerate()
    {
        // 輸入
        $input = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'Test201102145704',
            'MerchantTradeDate' => '2020/10/20 14:57:04',
            'PaymentType' => 'aio',
            'TotalAmount' => 100,
            'TradeDesc' => '檢查碼建立測試',
            'ItemName' => '測試商品 100 TWD x 1',
            'ReturnURL' => 'https://www.ecpay.com.tw/receive.php',
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
        ];

        // 預期結果
        $expected = '5DC5C11B7E9D42A5D750511E6BE08CDE759104F7EAB6CDD761AE120A5A8ABB34';

        // 執行
        $actual = $this->service->generate($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }

    public function testVerify()
    {
        // 輸入
        $input = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'Test201102145704',
            'MerchantTradeDate' => '2020/10/20 14:57:04',
            'PaymentType' => 'aio',
            'TotalAmount' => 100,
            'TradeDesc' => '檢查碼建立測試',
            'ItemName' => '測試商品 100 TWD x 1',
            'ReturnURL' => 'https://www.ecpay.com.tw/receive.php',
            'ChoosePayment' => 'ALL',
            'EncryptType' => 1,
            'CheckMacValue' => '5DC5C11B7E9D42A5D750511E6BE08CDE759104F7EAB6CDD761AE120A5A8ABB34',
        ];

        // 執行
        $actual = $this->service->verify($input);

        // 判斷
        $this->assertTrue($actual);
    }
}
