<?php

namespace Bravist\Cnvex;

use Bravist\Cnvex\Handlers\Http;
use Carbon\Carbon;

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
            'service'   => 'queryUser',
            'userId'    => $internalUid,
            'outUserId' => $externalUid,
        ]);
        if (!isset($response->userInfo[0])) {
            throw new \RuntimeException('未找到企账通用户');
        }
        $account = $response->userInfo[0];
        // 手机号码未实名认证
        // if ($account->mobileNoAuth != 'AUTH_OK') {
        //     throw new \RuntimeException('企账通账户手机号码未实名');
        // }
        // // 身份信息未实名认证
        // if ($account->realNameAuth != 'AUTH_OK') {
        //     throw new \RuntimeException('企账通账户身份信息未实名认证');
        // }
        // 未绑定银行卡
        // if ($account->bankCardCount < 1) {
        //     throw new \RuntimeException('企账通账户未绑定银行卡');
        // }

        // if ($account->status != 'ENABLE') {
        //     throw new \RuntimeException('企账通账户已被禁用');
        // }

        return $account;
    }

    /**
     * 查询企账通2.0账户信息，返回全部信息
     * @param  string $internalUid 企账通用户ID
     * @param  string $externalUid 企账通外部用户ID
     * @return object
     */
    public function queryUserReturnAll($internalUid = '', $externalUid = '')
    {
        $response = $this->post([
            'service'   => 'queryUser',
            'userId'    => $internalUid,
            'outUserId' => $externalUid,
        ]);
        if (!isset($response->userInfo[0])) {
            throw new \RuntimeException('未找到企账通用户');
        }
        $account = $response->userInfo;

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
            'userId'  => $internalUid,
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
        $type     = $internalUid ? 'BIND_BANK_CARD' : 'REGISTER';
        $response = $this->post([
            'service'        => 'smsCapthaSend',
            'userId'         => $internalUid,
            'mobile'         => $mobile,
            'smsCaptchaType' => $type,
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
            'service'           => 'commonAuditRegister',
            'outUserId'         => $externalUid,
            'type'              => 'PERSON',
            'grade'             => 'LV_1',
            'captcha'           => $captcha,
            'email'             => $email,
            'mobileNo'          => $mobile,
            'registerClient'    => $from,
            'personRegisterDto' => json_encode([
                'realName' => $realname,
                'certNo'   => $idCard,
                'bankCard' => $bankCard,
            ]),
        ]);
        //$this->addOperator($realname, $response->userId, $realname, $mobile, '注册默认操作员');
        return $response->userId;
    }

    /**
     * 企账通不同类型会员注册
     *
     * @param  string $externalUid 企账通外部用户ID
     * @param  integer $captcha 短信验证码
     * @param  integer $mobile 手机号码
     * @param  string $from 注册渠道 MOBILE/PC
     * @param  string $email 邮箱
     * @param  string $type 注册会员类型
     * @param  string $grade 实名审核申请等级
     * @param  object $personRegisterDto 个人用户注册信息
     * @param  object $individualRegisterDto 个体户注册信息
     * @param  object $businessRegisterDto 企业用户注册信息
     * @return string
     */
    public function commonAuditRegister($externalUid, $captcha, $mobile, $type, $grade,
                                        $personRegisterDto = '', $individualRegisterDto = '', $businessRegisterDto = '', $from = 'MOBILE', $email = ''){
        $response = $this->post([
            'service'               => 'commonAuditRegister',
            'outUserId'             => $externalUid,
            'type'                  => $type,
            'grade'                 => $grade,
            'captcha'               => $captcha,
            'email'                 => $email,
            'mobileNo'              => $mobile,
            'registerClient'        => $from,
            'personRegisterDto'     => $personRegisterDto,
            'individualRegisterDto' => $individualRegisterDto,
            'businessRegisterDto'   => $businessRegisterDto
        ]);
        return $response->userId;
    }

    /**
     * 开通二维码收款服务
     *
     * @param  string $userId 用户ID
     * @param  string $shopName 店铺名称
     * @param  string $doorheadPhotoPath 门头照地址
     * @param  integer $captcha 短信验证码
     * @param  integer $mobileNo 手机号码
     * @param  string $address 地址
     * @param  string $province 省份
     * @param  string $city 城市
     * @param  string $district 区/县
     * @return string
     */
    public function qrcodeApply($userId, $shopName, $doorheadPhotoPath, $captcha, $mobileNo, $province, $city, $district, $address)
    {
        $response = $this->post([
            'service'           => 'qrcodeApply',
            'userId'              => $userId,
            'shopName'            => $shopName,
            'doorheadPhotoPath'   => $doorheadPhotoPath,
            'captcha'             => $captcha,
            'mobileNo'            => $mobileNo,
            'province'            => $province,
            'city'                => $city,
            'district'            => $district,
            'address'             => $address
        ]);
        return $response;
    }

    /**
     * 发送验证码
     * @param integer $mobile 手机号码
     * @param integer $type 短信类型
     *      BIND_BANK_CARD:绑定银行卡
     *      REGISTER:注册用户
     *      LOGIN:登录
     *      FORGET_LOGIN_PASSWORD:找回登录密码
     *      FORGET_PAY_PASSWORD:找回支付密码
     *      COMMON_CAPTCHA:通用验证码
     * @param string $internalUid 企账通用户ID(当为绑卡短信时，必填)
     * @return boolean
     */
    public function smsCapthaSend($mobile, $type = 'REGISTER', $internalUid = '')
    {
        $response = $this->post([
            'service'        => 'smsCapthaSend',
            'userId'         => $internalUid,
            'mobile'         => $mobile,
            'smsCaptchaType' => $type,
        ]);
        return (boolean) $response->success;
    }

    /**
     * 店铺资料查询服务
     *
     * @param $shopId
     * @return string
     * @throws \Exception
     */
    public function queryShopInfo($shopId)
    {
        $response = $this->post([
            'service'           => 'queryShopInfo',
            'shopId'              => $shopId
        ]);
        return $response;
    }


    /**
     * 查询单个转账交易单
     * @param  string $orignalNo 商户订单号
     * @return object
     */
    public function queryTransfer($orignalNo)
    {
        $res = $this->post([
            'service'     => 'tradeQuery',
            'origOrderNo' => $orignalNo,
        ]);

        return $res->tradeOrder;
    }

    /**
     * 统一收单交易创建接口
     * @param  string $subject           订单标题
     * @param  float $amount             订单总金额，单位为元，精确到小数点后两位
     * @param  integer $seller           卖家企账通用户ID
     * @param  string $notify            异步回调通知地址
     * @param  string $transNo           商户交易单号
     * @param  integer $buyer            买家企账通用户ID
     * @param  string $clearType         清分类型: MANUAL 手动 AUTO 自动
     * @param  string $body              对交易或商品的描述
     * @param  String $goodsDetail       订单包含的商品列表信息.Json格式
     * @param  String $tradeProfitDetail 订单交易分润列表信息，Json格式
     * @return Object
     */
    public function createTransaction($subject, $amount, $seller, $notify, $transNo, $buyer = null, $clearType = 'AUTO', $body = '', $goodsDetail = null, $tradeProfitDetail = null, $tradeTime = null)
    {
        return $this->post([
            'service'               => 'tradeCreate',
            'merchOrderNo'          => $transNo,
            'tradeName'             => $subject,
            'sellerUserId'          => $seller,
            'buyerUserId'           => $buyer,
            'tradeProfitType'       => $clearType,
            'amount'                => floatval($amount),
            'tradeTime'             => $tradeTime ? $tradeTime : Carbon::now()->toDateTimeString(),
            'tradeMemo'             => $body,
            'notifyUrl'             => $notify,
            'userIp'                => get_client_ip(),
            'goodsInfoList'         => $goodsDetail,
            'tradeProfitInfoList'   => $tradeProfitDetail,
        ]);
    }

    /**
     * 微信扫码支付
     * @param  float $amount       支付金额
     * @param  string $notify      通知回调地址
     * @param  string $transNo     商户交易单号
     * @param  string $subject     交易订单标题
     * @param  string $internalUid 企账通用户ID
     * @param  string $payerAccountNo 企账通用户账户
     * @return object
     */
    public function payWechatQrCode($amount, $notify, $transNo, $subject, $internalUid = '', $payerAccountNo = '')
    {
        return $this->post([
            'service'      => 'wechatScanCodePay',
            'payerUserId'  => $internalUid,
            'payerAccountNo'  => $payerAccountNo,
            'productInfo'  => $subject,
            'amount'       => $amount,
            'merchOrderNo' => $transNo,
            'userIp'       => get_client_ip(),
            'notifyUrl'    => $notify,
        ]);
    }

    /**
     * 支付宝扫码支付
     * @param  float $amount       支付金额
     * @param  string $notify      通知回调地址
     * @param  string $transNo     商户交易单号
     * @param  string $subject     交易订单标题
     * @param  string $internalUid 企账通用户ID
     * @param  string $payerAccountNo 企账通用户账户
     * @return object
     */
    public function payAlipayQrCode($amount, $notify, $transNo, $subject, $internalUid = '', $payerAccountNo = '')
    {
        return $this->post([
            'service'      => 'aliPay',
            'payerUserId'  => $internalUid,
            'payerAccountNo'  => $payerAccountNo,
            'productInfo'  => $subject,
            'amount'       => $amount,
            'merchOrderNo' => $transNo,
            'userIp'       => get_client_ip(),
            'notifyUrl'    => $notify,
        ]);
    }

    /**
     * 微信APP原生支付
     * @param  float $amount       支付金额
     * @param  string $notify      通知回调地址
     * @param  string $transNo     商户交易单号
     * @param  string $subject     交易订单标题
     * @param  string $wechatAppId 微信APP支付开放平台应用ID
     * @param  string $deviceType IOS:IOS设备 ANDROID:android设备
     * @param  string $internalUid 企账通用户ID
     * @param  string $payerAccountNo 企账通用户账户
     * @return object
     */
    public function payWechatNative($amount, $notify, $transNo, $subject, $wechatAppId, $deviceType = '', $internalUid = '', $payerAccountNo = '')
    {
        return $this->post([
            'service'      => 'wechatAppPay',
            'appid'  => $wechatAppId,
            'deviceType'  => $deviceType,
            'productInfo'  => $subject,
            'payerUserId'  => $internalUid,
            'payerAccountNo'  => $payerAccountNo,
            'amount'       => $amount,
            'merchOrderNo' => $transNo,
            'userIp'       => get_client_ip(),
            'notifyUrl'    => $notify,
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
     * @param  integer $page 起始页
     * @param  integer $limit 页面大小，默认 20
     * @param  string  $startTime 开始日期
     * @param  string  $endTime   结束日期
     * @param  string  $tradeType 交易类型
     * @param  string  $startProfitTime 清分开始时间
     * @param  string  $endProfitTime   清分结束时间
     * @return object
     */
    public function queryTransfers($seller = '', $buyer = '', $status = 'SUCCESS', $page = 1, $limit = 20, $startTime = null, $endTime = null, $tradeType = '', $startProfitTime = null, $endProfitTime = null)
    {
        return $this->post([
            'service'      => 'tradeQueryPage',
            'sellerUserId' => $seller,
            'buyerUserId'  => $buyer,
            'tradeStatus'  => $status,
            'start'        => $page,
            'limit'        => $limit,
            'startTime'    => $startTime,
            'endTime'      => $endTime,
            'tradeType'    => $tradeType,
            'startProfitTime' => $startProfitTime,
            'endProfitTime'   => $endProfitTime,
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
            'service'     => 'fundQuery',
            'origOrderNo' => $orignalNo,
        ]);
        return $res->tradeOrderInfo;
    }

    /**
     * 查询多笔充值或者提现
    * @param string $internalUid 企账通用户ID
     * @param  integer $page  当前页
     * @param  integer $limit 页面个数
     * @param string $status INIT:初始状态;PROCESSING:处理中;SUCCESS:交易成功;FAIL:交易失败
     * @param  integer $page  当前页
     * @param  integer $limit 页面个数
     * @param  integer $page 起始页
     * @param  integer $limit 页面大小，默认 20
     * @param  string  $startTime 开始日期
     * @param  string  $endTime   结束日期
     * @param  string  $tradeType 交易类型
     * @return object
     */
    public function queryRechargesAndwithdrawals($internalUid, $page = 1, $status = 'SUCCESS', $limit = 20, $startTime = null, $endTime = null, $tradeType = '')
    {
        return $this->post([
            'service'    => 'fundQueryPage',
            'userId'     => $internalUid,
            'fundStatus' => $status,
            'start'      => $page,
            'limit'      => $limit,
            'tradeType'  => $tradeType,
            'startTime'  => $startTime,
            'endTime'    => $endTime,
        ]);
    }

    /**
     * 查询用户绑卡记录
     * @param  string  $internalUid 企账通用户ID
     * @param  string  $purpose 绑卡用途
     *                              IDDEDUCT:代扣; WITHDRAW:提现; PACT_BOTH:代扣提现
     * @param  string  $status  银行卡状态
     *                              APPLY:申请; UNACTIVATED:未激活; ENABLE:有效; DISABLE:无效
     * @param  integer $page        当前页
     * @param  integer $limit       页面个数
     * @return array
     */
    public function queryBankCards($internalUid, $purpose = null, $status = null, $page = 1, $limit = 20)
    {
        return $this->post([
            'service' => 'queryPact',
            'userId'  => $internalUid,
            'purpose' => $purpose,
            'status'  => $status,
            'start'   => $page,
            'limit'   => $limit,
        ]);
    }

    /**
     * 绑定对私银行卡
     * @param  string $internalUid  企账通用户ID
     * @param  string $mobile       手机号码
     * @param  integer $captcha      验证码
     * @param  string $bankCardNo   银行卡号
     * @param  string $purpose      绑卡用途,默认为 "WITHDRAW"
     *                                  DEDUCT:代扣; WITHDRAW:提现; PACT_BOTH:代扣提现
     * @param  string $bankCardType 卡种,默认为 "DEBIT_CARD"
     *                                  COMPANY_CARD:企业账户; CREDIT_CARD:贷记卡; DEBIT_CARD:借记卡; SEMI_CREDIT:准贷记卡; PREPAID:预付费卡; DEBIT_CREDIT:借贷一体; ALL:所有卡种
     * @return array
     */

    public function bindPrivateBankCard($internalUid, $mobile, $captcha, $bankCardNo, $purpose = 'WITHDRAW', $bankCardType = 'DEBIT_CARD')
    {
        return $this->post([
            'service'      => 'signCard',
            'userId'       => $internalUid,
            'mobile'       => $mobile,
            'captcha'      => intval($captcha),
            'bankCardNo'   => $bankCardNo,
            'publicTag'    => 'N',
            'purpose'      => $purpose,
            'bankCardType' => $bankCardType,
        ]);
    }

    /**
     * 绑定 对公/对公&&对私 银行卡
     * @param  string $internalUid  企账通用户ID
     * @param  string $mobile       手机号码
     * @param  integer $captcha     验证码
     * @param  string $bankCardNo   银行卡号
     * @param  string $bankName      银行名称，如 "工商银行"
     * @param  string $bankCode      银行简称，如 "ICBC"
     * @param  string $province      开户省，如 "重庆"
     * @param  string $city          开户市，如 "重庆"
     * @param  string $purpose      绑卡用途,默认为 "WITHDRAW"
     *                                  DEDUCT:代扣; WITHDRAW:提现; PACT_BOTH:代扣提现
     * @param  string $bankCardType 卡种,默认为 "DEBIT_CARD"
     *                       COMPANY_CARD:企业账户; CREDIT_CARD:贷记卡;DEBIT_CARD:借记卡;
     *                       SEMI_CREDIT:准贷记卡; PREPAID:预付费卡; DEBIT_CREDIT:借贷一体; ALL:所有卡种
     * @param  string $publicTag     银行卡账户类型，默认为 "Y"
     *                                   Y:对公; NY:对公&&对私
     * @return array
     */
    public function bindPublicBankCard($internalUid, $mobile, $captcha, $bankCardNo, $bankName, $bankCode, $province, $city, $purpose = 'WITHDRAW', $bankCardType = 'DEBIT_CARD', $publicTag = 'Y')
    {
        return $this->post([
            'service'      => 'signCard',
            'userId'       => $internalUid,
            'mobile'       => $mobile,
            'captcha'      => intval($captcha),
            'bankCardNo'   => $bankCardNo,
            'bankName'     => $bankName,
            'bankCode'     => $bankCode,
            'province'     => $province,
            'city'         => $city,
            'purpose'      => $purpose,
            'bankCardType' => $bankCardType,
            'publicTag'    => $publicTag,
        ]);
    }

    /**
     * 解绑银行卡
     * @param  string  $internalUid 企账通用户ID
     * @param  string  $bindId      签约流水号
     * @return array
     */
    public function unbindBankCard($internalUid, $bindId)
    {
        return $this->post([
            'service' => 'cardUnsign',
            'userId'  => $internalUid,
            'bindId'  => $bindId,
        ]);
    }

    /**
     * 查询支持的城市列表
     * @param   string $province 省份名称
     * @return  array
     */
    public function querySupportCity($province = null)
    {
        return $this->post([
            'service'  => 'querySupportCity',
            'province' => $province,
        ]);
    }

    /**
     * 查询操作员
     * @param  string  $internalUid 企账通用户ID
     * @return string
     */
    public function queryOperator($internalUid)
    {
        $res = $this->post([
            'service' => 'queryOperator',
            'userId'  => $internalUid,
        ]);

        $results = array_filter($res->operatorDtos, function ($item) {
            return $item->status == 'ENABLE';
        });
        return $results[0]->operator;
    }

    /**
     * 添加操作员
     * @param string $name
     * @param string $internalUid
     * @param string $realname
     * @param integer $mobile
     * @param string $comment
     */
    public function addOperator($name, $internalUid, $realname, $mobile, $comment = '')
    {
        return $this->post([
            'service'      => 'operatorAdd',
            'operatorName' => $name,
            'refUserId'    => $internalUid,
            'realName'     => $realname,
            'phone'        => $mobile,
            'comment'      => $comment,
        ]);
    }

    /**
     * 钱包跳转服务接口
     * @param string $internalUid 企账通用户ID
     * @param  string $target 目标页面，默认为首页
     * @param  string $title 是否显示页面抬头
     * @param  string $color 自定义界面主题颜色值
     * @return string
     */
    public function getWalletRedirectUrl($internalUid, $target = '', $title = '', $color = '', $operatorId = '')
    {
        return $this->getReturnUrl([
            'service'     => 'walletRedirect',
            'userId'      => $internalUid,
            'operatorId'  => $operatorId,
            'requestTime' => Carbon::now()->toDateTimeString(),
            'target'      => $target,
            'showTitle'   => $title,
            'themeColor'  => $color,
        ]);
    }

    /**
     * 转账，使用企账通余额支付
     * @param  string  $transNo      商户交易单号
     * @param  string  $transNo      通知URL
     * @param  integer $amount       付款金额
     * @param  string  $payerId      付款人企账通ID
     * @param  string  $payerAccount 付款人企账通账户
     * @return string
     */
    public function transfer($transNo, $notifyUrl, $amount, $payerId = '', $payerAccount = '')
    {
        return $this->post([
            'service'        => 'balancePay',
            'payerUserId'    => $payerId,
            'payerAccountNo' => $payerAccount,
            'amount'         => $amount,
            'userIp'         => get_client_ip(),
            'merchOrderNo'   => $transNo,
            'notifyUrl'      => $notifyUrl,
        ]);
    }

    /**
     * 提现
     * @param  string $bindId      代扣绑卡ID
     * @param  string $internalUid 企账通用户ID
     * @param  string $transNo     交易号
     * @param  int $amount          金额
     * @param  string $notifyUrl   通知URL
     * @param  string $accountNo   账户
     * @param  string $tradeTime   交易时间
     * @param  string $tradeMemo   备注
     * @return void
     */
    public function withdraw($bindId, $internalUid, $transNo, $amount, $notifyUrl, $accountNo = null, $tradeTime = null, $tradeMemo = null)
    {
        return $this->post([
            'service'    => 'withdraw',
            'bindId'     => $bindId,
            'userId'     => $internalUid,
            'accountNo'  => $accountNo,
            'amount'     => $amount,
            'tradeTime'  => $tradeTime ? $tradeTime : Carbon::now()->toDateTimeString(),
            'tradeMemo'  => $tradeMemo,
            'merchOrderNo'   => $transNo,
            'userIp'     => get_client_ip(),
            'notifyUrl'  => $notifyUrl,
        ]);
    }


    /**
     *
     * http://bxapi.cnvex.cn/apiService/intoServiceDetailInfo.html?serviceNo=offlineOrderSynchronize_1.0&schemeName=%E9%80%9A%E7%94%A8%E6%9C%8D%E5%8A%A1&schemeId=1#
     * @param  [type] $transNo
     * @param  [type] $posMerchantNo
     * @param  [type] $posClientNo
     * @param  [type] $posBatchNo
     * @param  [type] $posTransactionNo
     * @param  [type] $tradeName
     * @param  [type] $seller
     * @param  [type] $buyer
     * @param  [type] $notifyUrl
     * @param  [type] $bankName
     * @param  [type] $bankCardNo
     * @param  [type] $bankCardType
     * @param  string $bankAccountType  PUBLIC：对公， PRIVATE：对私
     * @param  string $clearingType
     * @param  string $tradeTime
     * @param  string $payer
     * @param  string $payerAccount
     * @param  string $sellerAccount
     * @param  string $buyerAccount
     * @param  string $remark
     * @param  string $body
     * @param  [type] $goodsDetail
     * @return [type]
     */
    public function posQrCodeOrderSync($transNo, $amount, $posMerchantNo, $posClientNo, $posBatchNo, $posTransactionNo, $tradeName, $seller, $buyer, $notifyUrl, $bankName, $bankCardNo, $bankCardType, $bankAccountType = 'PRIVATE', $clearingType = 'AUTO', $tradeTime = '', $payer = '', $payerAccount = '', $sellerAccount = '', $buyerAccount = '', $remark = '', $body = '', $goodsDetail = '', $posTradeNo = '', $posOutOrderNo = '')
    {
        return $this->post([
            'service'    => 'posOrderSynchronize',
            'tradeName'     => $tradeName,
            'posTradeNo'     => $posTradeNo,
            'posOutOrderNo'     => $posOutOrderNo,
            'posMerchantNo'     => $posMerchantNo,
            'posClientNo'     => $posClientNo,
            'posBatchNo'     => $posBatchNo,
            'posTransactionNo'     => $posTransactionNo,
            'sellerUserId'  => $seller,
            'sellerAccountNo'  => $sellerAccount,
            'sellerOrgName'  => $sellerAccount,
            'buyerUserId'  => $buyer,
            'buyerOrgName'  => $buyerAccount,
            'payerUserId'  => $payer,
            'payerAccountNo'  => $payerAccount,
            'bankName'  => $bankName,
            'bankCardNo'  => $bankCardNo,
            'bankCardType'  => $bankCardType,
            'bankAccountType'  => $clearingType,
            'amount'  => $amount,
            'tradeTime'  => $tradeTime ? $tradeTime : Carbon::now()->toDateTimeString(),
            'tradeMemo'  => $remark,
            'merchOrderNo'   => $transNo,
            'goodsInfoList'     => $goodsDetail,
            'tradeProfitInfoList' => $body,
            'notifyUrl'  => $notifyUrl,
            'tradeProfitType'  => $clearingType,
        ]);
    }

    /**
    * 提现跳转
    * http://bxapi.cnvex.cn/apiService/intoServiceDetailInfo.html?serviceNo=commonAuditRegister_1.0&schemeName=%E9%80%9A%E7%94%A8%E6%9C%8D%E5%8A%A1&schemeId=1#
    * @param  string $operatorId      代扣绑卡ID
    * @param  string $internalUid 企账通用户ID
    * @param  string $transNo     交易号
    * @param  int $amount          金额
    * @param  string $notifyUrl   通知URL
    * @param  string $accountNo   账户
    * @param  string $tradeTime   交易时间
    * @param  string $tradeMemo   备注
    * @return void
    */
    public function withdrawRedirect($operatorId, $internalUid, $transNo, $amount, $notifyUrl, $returnUrl = null,  $accountNo = null, $tradeTime = null, $tradeMemo = null)
    {
        return $this->getReturnUrl([
            'service'    => 'withdrawRedirect',
            'operatorId'     => $operatorId,
            'userId'     => $internalUid,
            'accountNo'  => $accountNo,
            'amount'     => $amount,
            'tradeTime'  => $tradeTime ? $tradeTime : Carbon::now()->toDateTimeString(),
            'tradeMemo'  => $tradeMemo,
            'merchOrderNo'   => $transNo,
            'userIp'     => get_client_ip(),
            'returnUrl' => $returnUrl,
            'notifyUrl'  => $notifyUrl,
        ]);
    }

    /**
     * Register redirect url
     * @param  [type] $outUserId [description]
     * @param  string $type      [description]
     * @param  [type] $notifyUrl [description]
     * @param  [type] $returnUrl [description]
     * @return [type]            [description]
     */
    public function getRegisterRedirectUrl($outUserId, $type = 'PERSON', $notifyUrl, $returnUrl)
    {
        return $this->getReturnUrl([
            'service'    => 'memberRegisterRedirect',
            'outUserId'     => $outUserId,
            'userType'  => $type,
            'returnUrl' => $returnUrl,
            'notifyUrl'  => $notifyUrl,
        ]);
    }

    /**
     * 跳转收银台支付
     * @param  [type] $internalUid    [description]
     * @param  [type] $transNo        [description]
     * @param  [type] $amount         [description]
     * @param  [type] $notifyUrl      [description]
     * @param  [type] $returnUrl      [description]
     * @param  string $tradeType      BALANCE_PAY(余额支付，pc和h5支持),NET_DEPOSIT_PAY(网银支付，pc支持),OFFLINE_PAY_PAY(线下支付，pc支持),WECHAT_PUBLIC_PAY(微信公众号支付，h5支持),WECHAT_SCAN_CODE_PAY(微信扫描支付，pc支持),ALI_SCAN_CODE_PAY(支付宝扫描支付，pc支持) 不传值表示支持所有支付方式
     * @param  [type] $payerAccountNo [description]
     * @param  [type] $operatorId     [description]
     * @return [type]                 [description]
     */
    public function getTradeRedirectPayUrl($internalUid, $transNo, $amount, $notifyUrl, $returnUrl, $tradeType = 'BALANCE_PAY', $payerAccountNo = null)
    {
        return $this->getReturnUrl([
            'service'           => 'tradeRedirectPay',
            'merchOrderNo'      => $transNo,
            'payerUserId'       => $internalUid,
            'payerAccountNo'    => $payerAccountNo,
            'tradeTypes'        => $tradeType,
            'amount'            => $amount,
            'operatorId'        => $internalUid,
            'returnUrl'         => $returnUrl,
            'notifyUrl'         => $notifyUrl,
        ]);
    }

    /**
     * 交易退款
     * @param  [type] $orignalNo [description]
     * @param  [type] $amount    [description]
     * @param  [type] $reason    [description]
     * @param  [type] $notifyUrl [description]
     * @return [type]            [description]
     */
    public function refund($orignalNo, $amount, $reason, $notifyUrl = '') 
    {
        return $this->post([
            'service'           => 'tradeRefund',
            'origMerchOrdeNo'   => $orignalNo,
            'refundAmount'      => $amount,
            'refundReason'      => $reason,
            'notifyUrl'         => $notifyUrl,
        ]);
    }

    /**
     * 交易订单清分服务
     * @param  [type] $orignalNo [description]
     */
    public function tradeProfit($orignalNo)
    {
        return $this->post([
            'service'           => 'tradeProfit',
            'origMerchOrdeNo'   => $orignalNo
        ]);
    }
}
