<?php

namespace Bravist\Cnvex;

use Bravist\Cnvex\Handlers\Http;

class Api extends Http
{
    /**
     * 查询企账通2.0账户信息
     * @param  string $internalUid 企账通用户ID
     * @param  string $externalUid 企账通外部用户ID
     * @return object
     */
    public function queryUser($internalUid = '', $externalUid = '')
    {
        $response = $this->post([
            'service' => 'queryUser',
            'userId' => $internalUid,
            'outUserId' => $externalUid
        ]);
        if (!isset($response->userInfo[0])) {
            throw new \RuntimeException('未找到企账通用户');
        }
        $account = $response->userInfo[0];
        // 手机号码未实名认证
        if ($account->mobileNoAuth != 'AUTH_OK') {
            throw new \RuntimeException('企账通账户手机号码未实名');
        }
        // 身份信息未实名认证
        if ($account->realNameAuth != 'AUTH_OK') {
            throw new \RuntimeException('企账通账户身份信息未实名认证');
        }
        // 未绑定银行卡
        if ($account->bankCardCount < 1) {
            throw new \RuntimeException('企账通账户未绑定银行卡');
        }

        if ($account->status != 'ENABLE') {
            throw new \RuntimeException('企账通账户已被禁用');
        }

        return $account;
    }

    /**
     * 查询企账通账户余额信息
     * @param string $internalUid 企账通用户ID
     * @return object
     */
    public function queryUserBalance($internalUid)
    {
        $response = $this->post([
            'service' => 'queryAccount',
            'userId' => $internalUid
        ]);
        if (!isset($response->account)) {
            throw new \RuntimeException('企账通余额账户信息错误');
        }
        return $response->account;
    }

    /**
     * 发送注册、绑卡短信验证码
     * @param integer $mobile 手机号码
     * @param string $internalUid 企账通用户ID
     * @return boolean
     */
    public function sendCaptcha($mobile, $internalUid = '')
    {
        $type = $internalUid ? 'BIND_BANK_CARD' : 'REGISTER';
        $response = $this->post([
            'service' => 'smsCapthaSend',
            'userId' => $internalUid,
            'mobile' => $mobile,
            'smsCaptchaType' => $type
        ]);
        return (boolean) $response->success;
    }

    /**
     * 注册个人企账通账户
     * @param  string $externalUid 企账通外部用户ID
     * @param  integer $captcha 短信验证码
     * @param  integer $mobile 手机号码
     * @param  string $realname 真实名称
     * @param  string $idCard 身份证号码
     * @param  string $bankCard 银行卡号
     * @param  string $from 注册渠道 MOBILE/PC
     * @param  string $email 邮箱
     * @return string
     */
    public function registerUser($externalUid, $captcha, $mobile, $realname, $idCard, $bankCard, $from = 'MOBILE', $email = '')
    {
        $response = $this->post([
            'service' => 'commonAuditRegister',
            'outUserId' => $externalUid,
            'type' => 'PERSON',
            'grade' => 'LV_1',
            'captcha' => $captcha,
            'email' => $email,
            'mobileNo' => $mobile,
            'registerClient' => $from,
            'personRegisterDto' => json_encode([
                'realName' => $realname,
                'certNo' => $idCard,
                'bankCard' => $bankCard
            ]),
        ]);
        return $response->userId;
    }

    /**
     * 查询单个转账交易单
     * @param  string $orignalNo 商户订单号
     * @return object
     */
    public function queryTransfer($orignalNo)
    {
        $res = $this->post([
            'service' => 'tradeQuery',
            'origOrderNo' => $orignalNo
        ]);

        return $res->tradeOrder;
    }

    /**
     * 统一收单交易创建接口
     * @param  string $subject 订单标题
     * @param  float $amount  订单总金额，单位为元，精确到小数点后两位
     * @param  integer $seller 卖家企账通用户ID
     * @param  string $notify 异步回调通知地址
     * @param  string $transNo 商户交易单号
     * @param  integer $buyer 买家企账通用户ID
     * @param  string $clearType 清分类型: MANUAL 手动 AUTO 自动
     * @param  string $body 对交易或商品的描述
     * @param  String $goodsDetail 订单包含的商品列表信息.Json格式
     * @return Object
     */
    public function createTransaction($subject, $amount, $seller, $notify, $transNo, $buyer = null, $clearType = 'AUTO', $body = '', $goodsDetail = null)
    {
        return $this->post([
            'service' => 'tradeCreate',
            'merchOrderNo' => $transNo,
            'tradeName' => $subject,
            'sellerUserId' => $seller,
            'buyerUserId' => $buyer,
            'tradeProfitType' => $clearType,
            'amount' =>  floatval($amount),
            'tradeTime' => date('Y-m-d H:i:s'),
            'tradeMemo' => $body,
            'notifyUrl' => $notify,
            'userIp' => get_client_ip(),
            'goodsInfoList' => $goodsDetail
        ]);
    }

    /**
     * 微信扫码支付
     * @param  float $amount      支付金额
     * @param  string $notify      通知回调地址
     * @param  string $transNo 商户交易单号
     * @param  string $internalUid 企账通用户ID
     * @return object
     */
    public function payWechatQrCode($amount, $notify, $transNo, $internalUid = '')
    {
        return $this->post([
            'service' => 'wechatScanCodePay',
            'payerUserId' => $internalUid,
            'amount' => $amount,
            'merchOrderNo' => $transNo,
            'userIp' => get_client_ip(),
            'notifyUrl' => $notify,
        ]);
    }

    /**
     * 查询转账交易记录
     * @param  string  $seller 卖家企账通用户ID
     * @param  string  $buyer  买家企账通用户ID
     * @param  string  $status INIT:初始状态
PROCESSING:支付中
SUCCESS:交易成功
FAIL:交易失败
CANCEL:交易撤销
REFUND:交易退款
REFUND_PROCESSING:交易退款中
CLOSE:交易关闭
     * @param  integer $page  当前页
     * @param  integer $limit 页面个数
     * @return array
     */
    public function queryTransfers($seller = '', $buyer = '', $status = 'SUCCESS', $page = 1, $limit = 20)
    {
        return $this->post([
            'service' => 'tradeQueryPage',
            'sellerUserId' => $seller,
            'buyerUserId' => $buyer,
            'tradeStatus' => $status,
            'start' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * 查询单笔充值或者提现
     * @param  string $orignalNo 商户交易单号
     * @return object
     */
    public function queryRechargeAndwithdrawal($orignalNo)
    {
        $res = $this->post([
            'service' => 'fundQuery',
            'origOrderNo' => $orignalNo
        ]);
        return $res->tradeOrderInfo;
    }

    /**
     * 查询多笔充值或者提现
     * @param string $internalUid 企账通用户ID
     * @param  integer $page  当前页
     * @param  integer $limit 页面个数
     * @param string $status INIT:初始状态;PROCESSING:处理中;SUCCESS:交易成功;FAIL:交易失败
     * @return array
     */
    public function queryRechargesAndwithdrawals($internalUid, $page = 1, $status = 'SUCCESS', $limit = 20)
    {
        $res = $this->post([
            'service' => 'fundQueryPage',
            'userId' => $internalUid,
            'fundStatus' => $status,
            'start' => $page,
            'limit' => $limit
        ]);
        return $res->rows;
    }

    /**
     * 查询用户绑卡记录
     * @param  string  $internalUid 企账通用户ID
     * @param  string  $bankPurpose 绑卡用途
     *                              IDDEDUCT:代扣; WITHDRAW:提现; PACT_BOTH:代扣提现
     * @param  string  $bankStatus  银行卡状态
     *                              APPLY:申请; UNACTIVATED:未激活; ENABLE:有效; DISABLE:无效
     * @param  integer $page        当前页
     * @param  integer $limit       页面个数
     * @return array
     */
    public function queryBankCards($internalUid, $bankPurpose = null, $bankStatus = null, $page = 1, $limit = 20)
    {
      $res = $this->post([
          'service' => 'queryPact',
          'userId' => $internalUid,
          'purpose' => $bankPurpose,
          'status' => $bankStatus,
          'start' => $page,
          'limit' => $limit
      ]);
      return $res;
    }
}
