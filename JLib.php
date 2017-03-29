<?php
require('./Wechat.php');
require('./func.php');

/**
 * 微信公众平台演示类
 */
class MyWeChat extends Wechat
{

    /**
     * 用户关注时触发
     *
     * @return void
     */
    protected function onSubscribe()
    {
        $this->responseText('欢迎上车, <a href="' . get_base_url() . 'intro.html">怎样快速学会开车?</a>');
    }

    /**
     * 用户取消关注时触发
     *
     * @return void
     */
    protected function onUnsubscribe()
    {
        // 「悄悄的我走了，正如我悄悄的来；我挥一挥衣袖，不带走一片云彩。」
    }

    /**
     * 收到文本消息时触发
     * 回复对应番号封面图以及磁力链接
     *
     * @return void
     */
    protected function onText()
    {
        $content = $this->getRequest('content');
        if (substr($content, 0, 2) == '@@') {
            // 只查询磁链(标清)
            $content = substr($content, 2);
            $this->responseText(get_magnet($content, false) ?: '请注意大写和连字符, 例如 ABS-130');
        } elseif (substr($content, 0, 1) == '@') {
            $content = substr($content, 1);
            $this->responseText(get_magnet($content) ?: '请注意大写和连字符, 例如 ABS-130');
        } else {
            // 查询全部信息
            $this->responseText(origin_query($content));
        }
        //$this->responseText('收到了文字消息：' . $this->getRequest('content'));
    }

    /**
     * 收到图片消息时触发
     *
     * @return void
     */
    protected function onImage()
    {
        $this->responseText('我怀疑你不想上车');
    }

    /**
     * 收到地理位置消息时触发
     *
     * @return void
     */
    protected function onLocation()
    {
        $this->responseText('你根本就不想上车');
    }

    /**
     * 收到链接消息时触发
     *
     * @return void
     */
    protected function onLink()
    {
        $this->responseText('我怀疑你是民进党');
    }

    /**
     * 收到未知类型消息时触发，回复收到的消息类型
     *
     * @return void
     */
    protected function onUnknown()
    {
        $this->responseText('我怀疑你是新司机');
    }

}

//$wechat = new MyWeChat('weixin', TRUE);  // 调试模式, 输出错误信息
$wechat = new MyWeChat('weixin');
$wechat->run();
