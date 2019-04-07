<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Gateways\Wechat;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\DataParser;
use Payment\Payment;

/**
 * @package Payment\Gateways\Alipay
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/3/28 10:21 PM
 * @version : 1.0.0
 * @desc    :
 **/
class BarCharge extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = 'pay/micropay';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {
            $xmlData = $this->buildParams($requestParams);
            $url     = sprintf($this->gatewayUrl, self::METHOD);

            $this->setHttpOptions($this->getCertOptions());
            $resXml = $this->postXML($url, $xmlData);

            $resArr = DataParser::toArray($resXml);
            if ($resArr['return_code'] !== self::REQ_SUC) {
                throw new GatewayException($resArr['return_msg'], Payment::GATEWAY_REFUSE, $resArr);
            }

            return $resArr;
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * @param array $params
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $params, array $requestParams)
    {
        $limitPay = self::$config->get('limit_pay', '');
        if ($limitPay) {
            $limitPay = $limitPay[0];
        } else {
            $limitPay = '';
        }
        $nowTime    = time();
        $expireTime = self::$config->get('timeout_express', '');
        $receipt    = $requestParams['receipt'] ?? false;
        $totalFee   = bcmul($requestParams['amount'], 100, 0);
        $sceneInfo  = $requestParams['scene_info'] ?? '';
        if ($sceneInfo) {
            $sceneInfo = json_encode(['store_info' => $sceneInfo]);
        } else {
            $sceneInfo = '';
        }

        $selfParams = [
            'device_info'      => $requestParams['device_info'] ?? '',
            'body'             => $requestParams['subject'] ?? '',
            'detail'           => $requestParams['body'] ?? '',
            'attach'           => $requestParams['return_param'] ?? '',
            'out_trade_no'     => $requestParams['order_no'] ?? '',
            'total_fee'        => $totalFee,
            'fee_type'         => self::$config->get('fee_type', 'CNY'),
            'spbill_create_ip' => $requestParams['client_ip'] ?? '',
            'goods_tag'        => $requestParams['goods_tag'] ?? '',
            'limit_pay'        => $limitPay,
            'time_start'       => date('YmdHis', $nowTime),
            'time_expire'      => $expireTime,
            'receipt'          => $receipt === true ? 'Y' : '',
            'auth_code'        => $requestParams['auth_code'] ?? '',
            'scene_info'       => $sceneInfo,
        ];
        $params = array_merge($params, $selfParams);
        return $params;
    }
}