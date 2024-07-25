<?php

namespace Ecpay\Sdk;

use Ecpay\Sdk\Exceptions\TransException;
use Ecpay\Sdk\TestCase\MultipleServiceTestCase;

final class PostWithAesTest extends MultipleServiceTestCase
{

    public function testAesStrResponseTransError()
    {
        // 輸入
        $merchantId = $this->stageOtpMerchantId;
        $service = $this->create('PostWithAesStrResponseService');
        $data = [
            'MerchantID' => $merchantId,
            'LogisticsID' => ['1848694'],
            'LogisticsSubType' => 'FAMI',
        ];
        $parameters = [
            'MerchantID' => $merchantId,
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '1.0.0',
            ],
            'Data' => $data,
        ];
        $url = 'https://logistics.ecpay.com.tw/Express/v2/PrintTradeDocument';

        $this->expectException(TransException::class);

        // 執行
        $service->post($parameters, $url);
    }

    public function testJsonResponse()
    {
        // 輸入
        $service = $this->create('PostWithAesJsonResponseService');
        $merchantTradeNo = 'Test' . time();
        $parameters = [
            'PlatformID'    => '',
            'MerchantID' => $this->stageOtpMerchantId,
            'RqHeader'      => [
                'Timestamp' => time(),
                'Revision'  => '1.0.0',
            ],
            'Data'  => [
                'MerchantID' => $this->stageOtpMerchantId,
                'MerchantTradeDate' => date('Y/m/d H:i:s'),
                'MerchantTradeNo' => $merchantTradeNo,
                'LogisticsType' => 'CB',
                'LogisticsSubType' => 'UNIMARTCBCVS',
                'GoodsAmount' => 1000,
                'GoodsWeight' => 5.0,
                'GoodsEnglishName' => 'Test goods',
                'ReceiverCountry' => 'SG',
                'ReceiverName' => 'Test Receiver',
                'ReceiverCellPhone' => '65212345678',
                'ReceiverStoreID' => '711_1',
                'ReceiverZipCode' => '419701',
                'ReceiverAddress' => 'address 23424 -fr 13-2',
                'ReceiverEmail' => 'test-receiver@ecpay.com.tw',
                'SenderName' => 'Test Sender',
                'SenderCellPhone' => '886987654321',
                'SenderAddress' => 'address 23424 -fr 13-2, Nangang Dist., Taipei City 115, Taiwan (R.O.C.)',
                'SenderEmail' => 'test-sender@ecpay.com.tw',
                'Remark' => 'Test Remark',
                'ServerReplyURL' => 'https://logistics-stage.ecpay.com.tw/MockMerchant/NoticsTestRtn',
            ],
        ];
        $url = 'https://logistics-stage.ecpay.com.tw/CrossBorder/Create';

        // 預期結果
        $expected = [
            'Data' => [
                'RtnCode' => 1,
                'RtnMsg' => '成功',
                'MerchantID' => (int) $this->stageOtpMerchantId,
                'MerchantTradeNo' => $merchantTradeNo,
                'LogisticsType' => 'CB',
                'LogisticsSubType' => 'UNIMARTCBCVS',
                'GoodsAmount' => 1000,
                'GoodsWeight' => 5.0,
                'ReceiverName' => 'Test Receiver',
                'ReceiverCellPhone' => '65212345678',
                'ReceiverCountry' => 'SG',
                'ReceiverEmail' => 'test-receiver@ecpay.com.tw',
                'ReceiverAddress' => 'address 23424 -fr 13-2',
            ],
            'MerchantID' => $this->stageOtpMerchantId,
            'PlatformID' => '',
            'TransCode' => 1,
            'TransMsg' => 'Success',
        ];

        // 執行
        $actual = $service->post($parameters, $url);

        // 判斷
        // 移除非固定值
        unset(
            $actual['RpHeader'],
            $actual['Data']['LogisticsID'],
            $actual['Data']['ShipmentNo'],
            $actual['Data']['UpdateStatusDate']
        );
        $this->assertEquals($expected, $actual);
    }

    public function testStrResponse()
    {
        // 輸入
        $service = $this->create('PostWithAesStrResponseService');
        $data = [
            'TempLogisticsID' => '0',
            'GoodsAmount' => $this->faker->numberBetween(1, 20000),
            'GoodsName' => '範例商品',
            'SenderName' => '陳大明',
            'SenderZipCode' => '11560',
            'SenderAddress' => '台北市南港區三重路19-2號6樓',
            'ServerReplyURL' => $this->faker->url,
            'ClientReplyURL' => $this->faker->url,
        ];
        $parameters = [
            'MerchantID' => $this->stageOtpMerchantId,
            'RqHeader' => [
                'Timestamp' => time(),
                'Revision' => '1.0.0',
            ],
            'Data' => $data,
        ];
        $url = 'https://logistics-stage.ecpay.com.tw/Express/v2/RedirectToLogisticsSelection';

        // 預期結果
        $expectedJs = 'vPostForm.submit();';

        // 執行
        $actual = $service->post($parameters, $url);
        $actualPosition = strpos($actual['body'], $expectedJs);

        // 判斷
        $this->assertTrue(($actualPosition !== false));
    }
}
