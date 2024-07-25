<?php
namespace Ecpay\Sdk;

use Ecpay\Sdk\Response\VerifiedArrayResponse;
use Ecpay\Sdk\TestCase\MultipleServiceTestCase;

final class PostWithCheckMacValueTest extends MultipleServiceTestCase
{
    public function testVerifiedArrayResponse()
    {
        // 輸入
        $service = $this->create(VerifiedArrayResponse::class);
        $input = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'WPLL4E341E122DB44D62',
            'PaymentDate' => '2019/05/09 00:01:21',
            'PaymentType' => 'Credit_CreditCard',
            'PaymentTypeChargeFee' => '1',
            'RtnCode' => '1',
            'RtnMsg' => '交易成功',
            'SimulatePaid' => '0',
            'TradeAmt' => '500',
            'TradeDate' => '2019/05/09 00:00:18',
            'TradeNo' => '1905090000188278',
            'CheckMacValue' => '59B085BAEC4269DC1182D48DEF106B431055D95622EB285DECD400337144C698',
        ];

        // 預期結果
        $expected = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'WPLL4E341E122DB44D62',
            'PaymentDate' => '2019/05/09 00:01:21',
            'PaymentType' => 'Credit_CreditCard',
            'PaymentTypeChargeFee' => '1',
            'RtnCode' => '1',
            'RtnMsg' => '交易成功',
            'SimulatePaid' => '0',
            'TradeAmt' => '500',
            'TradeDate' => '2019/05/09 00:00:18',
            'TradeNo' => '1905090000188278',
            'CheckMacValue' => '59B085BAEC4269DC1182D48DEF106B431055D95622EB285DECD400337144C698',
        ];

        // 執行
        $actual = $service->get($input);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
    
    public function testVerifiedEncodedStringResponse()
    {
        // 輸入
        $service = $this->create('PostWithCmvVerifiedEncodedStrResponseService');
        $parameters = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => '2019091711192742',
            'TimeStamp' => time(),
        ];
        $url = 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5';

        // 預期結果
        $expected = [
            'HandlingCharge' => '0',
            'ItemName' => 'Buy Some Products 390 TWD x 1',
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => '2019091711192742',
            'PaymentDate' => '',
            'PaymentType' => 'BARCODE_BARCODE',
            'PaymentTypeChargeFee' => '0',
            'TradeAmt' => '390',
            'TradeDate' => '2019/09/17 11:19:27',
            'TradeNo' => '1909171119275187',
            'TradeStatus' => '0',
            'CheckMacValue' => '756267B74D19AEB1EC1547CD47E9964AC10E2E2AF09613FB86DA200F262FBDF5',
        ];

        // 執行
        $actual = $service->post($parameters, $url);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
    
    public function testJsonResponse()
    {
        // 輸入
        $service = $this->create('PostWithCmvJsonResponseService');
        $parameters = [
            'MerchantID' => $this->stageOtpMerchantId,
            'CreditRefundId' => 10123456,
            'CreditAmount' => 100,
            'CreditCheckCode' => 59997889,
        ];
        $url = 'https://payment.ecPay.com.tw/CreditDetail/QueryTrade/V2';

        // 預期結果
        $expected = [
            'RtnMsg' => '找不到加密金鑰，請確認是否有申請開通此付款方式',
            'RtnValue' => null,
        ];

        // 執行
        $actual = $service->post($parameters, $url);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
    
    public function testEncodedStrResponse()
    {
        // 輸入
        $service = $this->create('PostWithCmvEncodedStrResponseService');
        $parameters = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => '5fa271cc74e51',
            'TradeNo' => '2011041718071855',
            'Action' => 'C',
            'TotalAmount' => 8685,
        ];
        $url = 'https://payment-stage.ecpay.com.tw/CreditDetail/DoAction';

        // 預期結果
        $expected = [
            'Merchant' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => '5fa271cc74e51',
            'RtnCode' => '1',
            'RtnMsg' => 'Succeeded.',
            'TradeNo' => '2011041718071855',
        ];

        // 執行
        $actual = $service->post($parameters, $url);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
    
    public function testStrResponse()
    {
        // 輸入
        $this->setFactory($this->getMd5CmvFactory());
        $service = $this->create('PostWithCmvStrResponseService');
        $parameters = [
            'MerchantID' => $this->stageOtpMerchantId,
            'GoodsAmount' => 1000,
            'ServiceType' => '4',
            'SenderName' => '功能測試',
            'ServerReplyURL' => 'https://www.ecpay.com.tw/example/server-reply',
        ];
        $url = 'https://logistics-stage.ecpay.com.tw/express/ReturnUniMartCVS';

        // 預期結果
        $expected = '%S|%s';

        // 執行
        $actual = $service->post($parameters, $url);

        // 判斷
        $this->assertStringMatchesFormat($expected, $actual['body']);
    }

    public function testQueryTradeEncodedStrResponse()
    {
        // 輸入
        $service = $this->create('PostWithCmvVerifiedEncodedStrResponseService');
        $parameters = [
            'MerchantID' => $this->stageOtpMerchantId,
            'MerchantTradeNo' => 'Test1667281224',
            'TimeStamp' => time(),
        ];
        $url = 'https://payment-stage.ecpay.com.tw/Cashier/QueryTradeInfo/V5';
        
        // 預期結果
        $expected = [
            'CheckMacValue' => 'CCE62DF2482C6C725B902F8161F2744E6B360ABA75F5D6E91B1442AAD5024F80',
            'CustomField1' => '',
            'CustomField2' => '',
            'CustomField3' => '',
            'CustomField4' => '',
            'HandlingCharge' => '5', 
            'ItemName' => '範例商品一批 A+B-C', 
            'MerchantID' => $this->stageOtpMerchantId, 
            'MerchantTradeNo' => 'Test1667281224', 
            'PaymentDate' => '2022/11/01 13:41:09', 
            'PaymentType' => 'Credit_CreditCard', 
            'PaymentTypeChargeFee' => '5', 
            'StoreID' => '',
            'TradeAmt' => '100', 
            'TradeDate' => '2022/11/01 13:40:24', 
            'TradeNo' => '2211011340247525', 
            'TradeStatus' => '1', 
        ];

        // 執行
        $actual = $service->post($parameters, $url);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
}
