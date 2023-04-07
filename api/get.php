<?php
header("Content-type: application/json; charset=UTF-8");
include_once("req.php");
include_once("collect.php");

// $con = Req::args("con");
$act = Req::args("act");
$get = new Get();
if ($act == "get_info") {
    $get->get_info();
} elseif ($act == "get_html") {
    $get->get_info();
} elseif ($act == "get_pc_html") {
    $get->get_info();
} elseif ($act == "get_isbn") {
    $get->get_info();
} else {
    die(json_encode(['code' => 400, 'msg' => 'empty']));
}


class Get
{
    public function init()
    {
    }

    /**
     * 读取豆瓣读书书籍信息，返回JSON
     * 此内容不包含ISBN
     */
    public function get_info()
    {
        $id = intval(Req::args("id"));
        if ($id == 0) {
            die(json_encode(['code' => 400, 'msg' => 'id not is null']));
        }
        $curl = new Collect($id);
        echo $curl->book_phone_json();
    }

    /**
     * 读取豆瓣读书书籍源代码，含有isbn
     */
    public function get_html()
    {
        $id = intval(Req::args("id"));
        if ($id == 0) {
            die(json_encode(['code' => 400, 'msg' => 'id not is null']));
        }
        $curl = new Collect($id);
        echo $curl->book_pc_html($id);
    }

    /**
     * 读取豆瓣读书书籍源代码，含有isbn
     */
    public function get_pc_html()
    {
        $url = Req::args("url");
        if (isset($url) and  (strlen($url) == 0 or $url == "" or $url == null)) {
            die(json_encode(['code' => 400, 'msg' => 'URL cannot be empty']));
        }
        $curl = new Collect(36210180);
        echo $curl->get_pc_html($url);
    }

    /**
     * 获取isbn
     */
    public function get_isbn()
    {
        $id = intval(Req::args("id"));
        if ($id == 0) {
            die(json_encode(['code' => 400, 'msg' => 'id not is null']));
        }
        $curl = new GetIsbn($id);
        $isbn = $curl->isbn();
        if (isset($isbn) and $isbn) {
            echo json_encode(['code' => 200, 'msg' => 'success', 'isbn' => $isbn, 'id' => $id]);
        } else {
            echo json_encode(['code' => 400, 'msg' => 'failed to get isbn', 'id' => $id]);
        }
    }

    /////////end class///////////////////////////////////////////////////////////////
}
