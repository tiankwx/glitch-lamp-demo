<?php

/**
 * 统一采集
 */

include_once("cache.php");
include_once("chash.php");

class Collect
{
    public $bid, $uid, $ll, $h5Bid, $id, $cache, $cacheTime = 10800, $infoCacheTime = 10800;
    public $name = "Collect:cookie";
    public function __construct($id = 0)
    {
        if (intval($id) == 0) {
            return null;
        }
        $this->cache = new Cache();
        $this->id = $id;
        $this->get_cookies();
    }

    /**
     * Get the cookie
     */
    public function get_cookie()
    {

        return $this->cache->get($this->name);
    }

    /**
     * 读取设置cookie
     */
    private function get_cookies()
    {
        $content = $this->get_cookie();
        if (isset($content) and is_array($content) and count($content) > 0) {
            if (isset($content['bid']) and isset($content['ll']) and isset($content['uid']) and isset($content['h5Bid'])) {
                $this->bid = $content['bid'];
                $this->uid = $content['uid'];
                $this->ll = $content['ll'];
                $this->h5Bid = $content['h5Bid'];
            } else {
                $this->write_cookie();
            }
        } else {
            $this->write_cookie();
        }
    }

    /**
     * Write cookie
     */
    private function write_cookie()
    {
        $this->bid = CHash::random(11, 'bid');
        $this->uid = CHash::uuid();
        $this->ll = rand(100000, 199999);
        $this->h5Bid = CHash::random(11, 'bid');
        $temp["bid"] = $this->bid;
        $temp['uid'] = $this->uid;
        $temp['ll'] = $this->ll;
        $temp['h5Bid'] = $this->h5Bid;
        $temp['time'] = time() + $this->cacheTime;
        $temp['date'] = date("Y-m-d H:i:s", $temp['time']);
        $this->cache->set($this->name, $temp, $this->cacheTime);
    }

    /**
     * 读取手机版本书籍信息，返回JSON
     * 此内容不包含ISBN
     */
    public function book_phone_json()
    {
        $info = ["msg" => "error", "code" => 404, "id" => $this->id];
        $name = "Collect:book_phone_json:" . $this->id;
        $cacheName = "stop_book_phone_json";
        $number = intval($this->cache->get($cacheName));
        if ($number >= 999) { // html采集触发反爬
            $info['msg'] = "collect error";
            $info['code'] = 500;
        } else {
            $info = $this->cache->get($name);
            if ($info) {
                return $info;
            }
            $string = "";
            $string .= "curl -fsS 'https://m.douban.com/rexxar/api/v2/book/" . $this->id . "?ck=&for_mobile=1' \\\n";
            $string .= "  -H 'Accept: application/json' \\\n";
            $string .= "  -H 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6,ru;q=0.5,pt;q=0.4' \\\n";
            $string .= "  -H 'Connection: keep-alive' \\\n";
            $string .= "  -H 'Cookie: bid=" . $this->h5Bid . "; ll=\"$this->ll\"; arp_scroll_position=0' \\\n";
            $string .= "  -H 'DNT: 1' \\\n";
            $string .= "  -H 'Referer: https://m.douban.com/book/subject/$this->id/?refer=home' \\\n";
            $string .= "  -H 'Sec-Fetch-Dest: empty' \\\n";
            $string .= "  -H 'Sec-Fetch-Mode: cors' \\\n";
            $string .= "  -H 'Sec-Fetch-Site: same-origin' \\\n";
            $string .= "  -H 'User-Agent: Mozilla/5.0 (Linux; Android 11; SAMSUNG SM-G973U) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/14.2 Chrome/87.0.4280.141 Mobile Safari/537.36' \\\n";
            $string .= "  -H 'X-Requested-With: XMLHttpRequest' \\\n";
            // 在指定目录中建立一个具有唯一文件名的文件。如果该目录不存在或不可写，tempnam() 会在系统临时目录中生成一个文件，并返回该文件包含文件名的完整路径。
            $file = tempnam(sys_get_temp_dir(), "book_phone_json_" . $this->id);
            $string .= "  --compressed > " . $file;
            system($string);
            if (file_exists($file)) {
                $files = file_get_contents($file);
                if (isset($files) and strlen($files) > 10) {
                    $info = json_decode($files, true);
                    $info['msg'] = "success";
                    $info['code'] = 200;
                } else {
                    // 获取不到信息暂停采集一小时
                    $this->cache->set($cacheName, 1000, $this->infoCacheTime);
                }
                unlink($file);
            }
        }
        $info = json_encode($info, JSON_UNESCAPED_UNICODE);
        $this->cache->set($name, $info, $this->infoCacheTime);
        return $info;
    }

    /**
     * 读取豆瓣读书书籍源代码，已获取isbn
     */
    public function book_pc_html()
    {
        $name = "Collect:book_pc_html:" . $this->id;
        $info = $this->cache->get($name);
        if ($info) {
            return $info;
        }
        $info = ["msg" => "error", "code" => 404, "id" => $this->id];
        $fail_name = "get_pc_html_fail_name";
        $number = intval($this->cache->get($fail_name));
        if ($number >= 999) { // html采集触发反爬
            $info['msg'] = "collect error";
            $info['code'] = 500;
        } else {
            $info = ["msg" => "error", "code" => 404];
            $string = "";
            $string .= "curl -fsS 'https://book.douban.com/subject/" . $this->id . "/' \\\n";
            $string .= "  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7' \\\n";
            $string .= "  -H 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6,ru;q=0.5,pt;q=0.4' \\\n";
            $string .= "  -H 'Cache-Control: max-age=0' \\\n";
            $string .= "  -H 'Connection: keep-alive' \\\n";
            $string .= "  -H 'Cookie: bid=" . $this->bid . "; gr_user_id=" . $this->uid . "; douban-fav-remind=1; ll=\"$this->ll\"; viewed=\"$this->id\"; arp_scroll_position=0' \\\n";
            $string .= "  -H 'DNT: 1' \\\n";
            $string .= "  -H 'Referer: https://book.douban.com/' \\\n";
            $string .= "  -H 'Sec-Fetch-Dest: document' \\\n";
            $string .= "  -H 'Sec-Fetch-Mode: navigate' \\\n";
            $string .= "  -H 'Sec-Fetch-Site: same-origin' \\\n";
            $string .= "  -H 'Sec-Fetch-User: ?1' \\\n";
            $string .= "  -H 'Upgrade-Insecure-Requests: 1' \\\n";
            $string .= "  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/111.0' \\\n";
            $string .= "  -H 'sec-ch-ua-mobile: ?0' \\\n";
            $file = tempnam(sys_get_temp_dir(), "book_pc_html_" . $this->id);
            $string .= "  --compressed > " . $file;
            system($string);
            if (file_exists($file)) {
                $info['data'] = file_get_contents($file);
                if (isset($info['data']) and strlen($info['data']) < 360) {
                    $this->cache->set($fail_name, 1000, $this->infoCacheTime);
                    $this->cache->delete($this->name); // 删除缓存 
                    $info['msg'] = "collect error";
                    $info['code'] = 500;
                } else {
                    $info['msg'] = "success";
                    $info['code'] = 200;
                }
                unlink($file);
            }
        }
        $info = json_encode($info, JSON_UNESCAPED_UNICODE);
        $this->cache->set($name, $info, $this->infoCacheTime);
        return $info;
    }

    /**
     * 读取书籍简略信息，返回html源代码
     */
    // 采集后一会被封IP了，换回 
    // preg_match_all("/<td>(.*?)<\/td>/i", $html['data'], $isbns);
    // if (isset($isbns['1']['0'])) {
    //     $i = 0;
    //     foreach ($isbns['1'] as $key) {
    //         if ($key == "ISBN") {
    //             $i++;
    //         }
    //         if ($i == 1) {
    //             $isbn = $key;
    //         }
    //     }
    // }
    public function book_pc_html_desc()
    {
        $name = "Collect:book_html_desc:" . $this->id;
        $info = $this->cache->get($name);
        if ($info) {
            return $info;
        }
        $info = ["msg" => "error", "code" => 404];
        $string = "";
        $string .= "curl -fsS 'https://www.douban.com/doubanapp/h5/book/{$this->id}/desc' \\\n";
        $string .= "  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7' \\\n";
        $string .= "  -H 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6,ru;q=0.5,pt;q=0.4' \\\n";
        $string .= "  -H 'Cache-Control: max-age=0' \\\n";
        $string .= "  -H 'Connection: keep-alive' \\\n";
        $string .= "  -H 'Cookie: bid=" . $this->bid . "; gr_user_id=" . $this->uid . "; douban-fav-remind=1; ll=\"$this->ll\"; viewed=\"$this->id\"; arp_scroll_position=0' \\\n";
        $string .= "  -H 'DNT: 1' \\\n";
        $string .= "  -H 'Sec-Fetch-Dest: document' \\\n";
        $string .= "  -H 'Sec-Fetch-Mode: navigate' \\\n";
        $string .= "  -H 'Sec-Fetch-Site: none' \\\n";
        $string .= "  -H 'Sec-Fetch-User: ?1' \\\n";
        $string .= "  -H 'Upgrade-Insecure-Requests: 1' \\\n";
        $string .= "  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36 Edg/111.0.1661.51' \\\n";
        $string .= "  -H 'sec-ch-ua: \"Microsoft Edge\";v=\"111\", \"Not(A:Brand\";v=\"8\", \"Chromium\";v=\"111\"' \\\n";
        $string .= "  -H 'sec-ch-ua-mobile: ?0' \\\n";
        $string .= "  -H 'sec-ch-ua-platform: \"Windows\"' \\\n";
        $file = tempnam(sys_get_temp_dir(), "book_html_desc_" . $this->id);
        $string .= "  --compressed > " . $file;
        system($string);
        if (file_exists($file)) {
            $info['data'] = file_get_contents($file);
            $info['msg'] = "success";
            $info['code'] = 200;
            unlink($file);
        }
        $info = json_encode($info, JSON_UNESCAPED_UNICODE);
        $this->cache->set($name, $info, $this->infoCacheTime);
        return $info;
    }

    /**
     * 手机版本里有读取电商信息的JSON里含有ISBN，此是第一处读取
     */
    public function book_phone_sale_isbn1()
    {
        $name = "Collect:book_phone_sale_isbn1:" . $this->id;
        $info = $this->cache->get($name);
        if ($info) {
            return $info;
        }
        $info = ["msg" => "error", "code" => 404];
        $string = "";
        $string .= "curl -s 'https://m.douban.com/rexxar/api/v2/market/book/{$this->id}?appVersion=4.18&ck=&for_mobile=1' \\\n";
        $string .= "-H 'Accept: application/json' \\\n";
        $string .= "-H 'Referer: https://m.douban.com/book/subject/{$this->id}/?refer=home' \\\n";
        $string .= "-H 'DNT: 1' \\\n";
        $string .= "-H 'X-Requested-With: XMLHttpRequest' \\\n";
        $string .= "-H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1 Edg/111.0.0.0' \\\n";
        // 在指定目录中建立一个具有唯一文件名的文件。如果该目录不存在或不可写，tempnam() 会在系统临时目录中生成一个文件，并返回该文件包含文件名的完整路径。
        $file = tempnam(sys_get_temp_dir(), "book_phone_sale_isbn1_" . $this->id);
        $string .= "  --compressed > " . $file;
        system($string);
        if (file_exists($file)) {
            $info = json_decode(file_get_contents($file), true);
            $info['msg'] = "success";
            $info['code'] = 200;
            unlink($file);
        }
        $info = json_encode($info, JSON_UNESCAPED_UNICODE);
        $this->cache->set($name, $info, $this->infoCacheTime);
        return $info;
    }

    /**
     * 手机版本里有读取电商信息的JSON里含有ISBN，此是第二处读取
     */
    public function book_phone_sale_isbn2()
    {
        $name = "Collect:book_phone_sale_isbn2:" . $this->id;
        $info = $this->cache->get($name);
        if ($info) {
            return $info;
        }
        $info = ["msg" => "error", "code" => 404];
        $string = "";
        $string .= "curl 'https://read.douban.com/j/subject_works?subject_id=" . $this->id . "&ck=&for_mobile=1' \\\n";
        $string .= "-H 'Accept: application/json' \\\n";
        $string .= "-H 'Referer: https://m.douban.com/' \\\n";
        $string .= "-H 'DNT: 1' \\\n";
        $string .= "-H 'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1 Edg/111.0.0.0' \\\n";
        // 在指定目录中建立一个具有唯一文件名的文件。如果该目录不存在或不可写，tempnam() 会在系统临时目录中生成一个文件，并返回该文件包含文件名的完整路径。
        $file = tempnam(sys_get_temp_dir(), "book_phone_sale_isbn2_" . $this->id);
        $string .= "  --compressed > " . $file;
        system($string);
        if (file_exists($file)) {
            $info = json_decode(file_get_contents($file), true);
            $info['msg'] = "success";
            $info['code'] = 200;
            unlink($file);
        }
        $info = json_encode($info, JSON_UNESCAPED_UNICODE);
        $this->cache->set($name, $info, $this->infoCacheTime);
        return $info;
    }


    /**
     * 读取指定页的PC端源代码
     * @param string $url 待采集的URL
     * @param int $time 缓存保存时间
     * @return string
     */
    public function get_pc_html($url, $time = 10800)
    {
        $info = ["msg" => "error", "code" => 404, "url" => $url];
        if (strlen($url) <= 0 or $url == null or $url == "") {
            $info = ["msg" => "url is null", "code" => 404];
            $info = json_encode($info, JSON_UNESCAPED_UNICODE);
            return $info;
        }
        $name = "Collect:get_pc_html:" . md5($url);
        $infos = $this->cache->get($name);
        if ($infos and strlen($infos) > 6) {
            return $infos;
        }
        $fail_name = "get_pc_html_fail_name";
        $number = intval($this->cache->get($fail_name));
        if ($number >= 999) { // html采集触发反爬
            $info['msg'] = "collect error";
            $info['code'] = 500;
            $info = json_encode($info, JSON_UNESCAPED_UNICODE);
            return $info;
        }
        $check = Tools::is_base64($url);
        if ($check) {
            $url = base64_decode($url);
        } else {
            return json_encode(['code' => 400, 'msg' => 'URL cannot be empty']);
        }
        $string = "";
        $string .= "curl '" . $url . "' \\\n";
        $string .= "  -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7' \\\n";
        $string .= "  -H 'Accept-Language: zh-CN,zh;q=0.9,en;q=0.8,en-GB;q=0.7,en-US;q=0.6,ru;q=0.5,pt;q=0.4' \\\n";
        $string .= "  -H 'Cache-Control: max-age=0' \\\n";
        $string .= "  -H 'Connection: keep-alive' \\\n";
        $string .= "  -H 'Cookie: bid=" . $this->bid . "; gr_user_id=" . $this->uid . "; ap_v=0,6.0; arp_scroll_position=" . rand(300, 2591) . ".199951171875; ' \\\n";
        $string .= "  -H 'DNT: 1' \\\n";
        $string .= "  -H 'Referer: https://book.douban.com/' \\\n";
        $string .= "  -H 'Sec-Fetch-Dest: document' \\\n";
        $string .= "  -H 'Sec-Fetch-Mode: navigate' \\\n";
        $string .= "  -H 'Sec-Fetch-Site: same-origin' \\\n";
        $string .= "  -H 'Sec-Fetch-User: ?1' \\\n";
        $string .= "  -H 'Upgrade-Insecure-Requests: 1' \\\n";
        $string .= "  -H 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36 Edg/111.0.1661.54' \\\n";
        $string .= "  -H 'sec-ch-ua: \"Microsoft Edge\";v=\"111\", \"Not(A:Brand\";v=\"8\", \"Chromium\";v=\"111\"' \\\n";
        $string .= "  -H 'sec-ch-ua-mobile: ?0' \\\n";
        $string .= "  -H 'sec-ch-ua-platform: \"Windows\"' \\\n";
        $file = tempnam(sys_get_temp_dir(), "get_pc_html_" . md5($url));
        $string .= "  --compressed > " . $file;
        system($string);
        if (file_exists($file)) {
            $info['url'] = $url;
            $info['data'] = file_get_contents($file);
            if (isset($info['data']) and strlen($info['data']) < 360) {
                $this->cache->set($fail_name, 1000, $this->infoCacheTime);
                $this->cache->delete($this->name); // 删除缓存 
                $info['msg'] = "collect error";
                $info['code'] = 500;
            } else {
                $info['msg'] = "success";
                $info['code'] = 200;
            }
            unlink($file);
        }
        $info = json_encode($info, JSON_UNESCAPED_UNICODE);
        $this->cache->set($name, $info, $time);
        return $info;
    }

    ////////////class end////////////////////////////////////////////////////////////////////////////////////
}



/**
 * 工具类
 */
class Tools
{

    /**
     * 保存数据库里时，处理特殊字符
     */
    public static function htmlentities($string = null)
    {
        if ($string == null) {
            return $string;
        }
        $string = htmlentities($string, ENT_QUOTES); // 处理单双引号
        $string = str_replace("\\", "\\\\", $string);
        return $string;
    }

    /**
     * 判断是否BASE64
     */
    public static function is_base64($str = null)
    {
        if ($str == null) {
            return false;
        } else {
            $check1 = ($str === base64_encode(base64_decode($str)) ? true : false);
            $check2 = ($str === base64_encode(base64_decode($str)) ? true : false);
            if ($check1 and $check2) {
                return true;
            } else {
                return false;
            }
        }
    }
}
