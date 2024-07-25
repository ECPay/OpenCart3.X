<?php

namespace Ecpay\Sdk;

use Ecpay\Sdk\TestCase\MultipleServiceTestCase;

final class AutoSubmitFormTest extends MultipleServiceTestCase
{
    public function testGenerateAutoSubmitForm()
    {
        // 輸入
        $service = $this->create('AutoSubmitFormService');
        $parameters = [
            'MerchantID' => 'MID',
            'MerchantTradeNo' => 'OrderNo'
        ];
        $action = 'https://www.ecpay.com.tw/action.php';
        $target = '_self';
        $id = 'test-form';
        $submitText = 'Testing';

        // 預期結果
        $expected = '<!DOCTYPE html>';
        $expected .= '<html>';
        $expected .= '<head>';
        $expected .= '<meta charset="utf-8">';
        $expected .= '</head>';
        $expected .= '<body>';
        $expected .= '<form id="test-form" method="POST" target="_self" action="https://www.ecpay.com.tw/action.php">';
        $expected .= '<input type="hidden" name="MerchantID" value="MID">';
        $expected .= '<input type="hidden" name="MerchantTradeNo" value="OrderNo">';
        $expected .= '</form>';
        $expected .= '<script type="text/javascript">';
        $expected .= 'document.getElementById("test-form").submit();';
        $expected .= '</script>';
        $expected .= '</body>';
        $expected .= '</html>';

        // 執行
        $actual = $service->generate($parameters, $action, $target, $id, $submitText);

        // 判斷
        $this->assertEquals($expected, $actual);
    }

    public function testGenerateAutoSubmitFormWithCheckMacValue()
    {
        // 輸入
        $service = $this->create('AutoSubmitFormWithCmvService');
        $parameters = [
            'MerchantID' => 'MID',
        ];
        $action = 'https://www.ecpay.com.tw/action.php';
        $target = '_self';
        $id = 'test-form';
        $submitText = 'Testing';

        // 預期結果
        $checkMacValue = '213F122F9797DBDCAC883CC5EE1C0E249A11A50DBF364578EB93316B2FF870A1';
        $expected = '<!DOCTYPE html>';
        $expected .= '<html>';
        $expected .= '<head>';
        $expected .= '<meta charset="utf-8">';
        $expected .= '</head>';
        $expected .= '<body>';
        $expected .= '<form id="test-form" method="POST" target="_self" action="https://www.ecpay.com.tw/action.php">';
        $expected .= '<input type="hidden" name="CheckMacValue" value="' . $checkMacValue . '">';
        $expected .= '<input type="hidden" name="MerchantID" value="MID">';
        $expected .= '</form>';
        $expected .= '<script type="text/javascript">';
        $expected .= 'document.getElementById("test-form").submit();';
        $expected .= '</script>';
        $expected .= '</body>';
        $expected .= '</html>';

        // 執行
        $actual = $service->generate($parameters, $action, $target, $id, $submitText);

        // 判斷
        $this->assertEquals($expected, $actual);
    }
}
