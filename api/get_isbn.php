<?php

class GetIsbn
{
    public $id, $isbn, $curl;
    public function __construct($id = 0)
    {
        $this->id = $id;
        $this->isbn = null;
        if (intval($id) > 0) {
            $this->curl = new Collect($this->id);
        }
    }

    /**
     * 汇总三种方式获取ISBN
     * @param int $type 1 直返返回ISBN 2 返回json 3 返回数组
     * @return string|array
     */
    public function isbn()
    {
        if (isset($this->curl)) {
            if ($this->method1() == null) {
                if ($this->method2() == null) {
                    $this->method3();
                }
            }
        }
        return $this->isbn;
    }

    /**
     * 获取isbn方式一，读取HTML源代码，容易限制
     */
    public function method1()
    {
        // 方式一 读取源代码
        $html =  $this->curl->book_pc_html();
        $html = json_decode($html, true);
        if (isset($html['data']) and strlen($html['data']) > 1000) {
            // 正则匹配ISBN
            preg_match("/book:isbn\".content=\"(.*?)\"/i", $html['data'], $isbns);
            if (isset($isbns['1']) and strlen($isbns['1']) > 1) {
                $this->isbn = $isbns['1'];
                return $this->isbn;
            }
        }
        return null;
    }

    /**
     * 方式二 手机端购书一，有的有，有的没有
     */
    public function method2()
    {
        $html = $this->curl->book_phone_sale_isbn1();
        preg_match("/isbn=(\d{3,15})&source=/i", $html, $isbns);
        if (isset($isbns['1']) and strlen($isbns['1']) > 3) {
            $this->isbn = $isbns['1'];
            return $isbns['1'];
        }
        return null;
    }

    /**
     * 方式三 手机端购书二，有的有，有的没有
     */
    public function method3()
    {
        $html = $this->curl->book_phone_sale_isbn2();
        preg_match("/isbn=(\d{3,15})&source=/i", $html, $isbns);
        if (isset($isbns['1']) and strlen($isbns['1']) > 3) {
            $this->isbn = $isbns['1'];
            return $isbns['1'];
        }
        return null;
    }
    ////////////class end////////////////////////////////////////////////////////////////////////////////////
}
