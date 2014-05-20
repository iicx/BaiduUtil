<?php
if($_POST){
  require 'baidu.php';
  session_start();
  foreach ($_POST as &$data) {
    $data=trim($data);
  }
  $username=$_POST['username'];
  $password=$_POST['password'];
  @$vcode=$_POST['vcode'];
  try{
     $client =  json_decode('{"_client_id":"wappc_1386816224047_167","_client_type":1,"_client_version":"6.0.1","_phone_imei":"a6ca20a897260bb1a1529d1276ee8176","cuid":"96D360F8BCF3AF6DA212A1429F6B2D75|046284918454666","model":"M1"}',true);
    $test_login=new BaiduUtil(NULL,$client);
    if(empty($vcode)){
      $result=$test_login->login($username,$password);
    }else{
      $result=$test_login->login($username,$password,$vcode,$_SESSION['vcode_md5']);
    }
  }catch(exception $e){

 }
  switch ($result['status']) {
    case 0:
        var_dump($result['data']['bduss']);
        break;
    case 5:
        $_SESSION['vcode_md5'] = $result['data']['vcode_md5'];
        $need_vcode = 1;
        break;
    default:
        var_dump($test_login);
      break;
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>百度登录测试页</title>
  <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
  <meta http-equiv="Content-Type" content="text/html;charset=UTF-8">
  <link rel="stylesheet" href="http://cdn.bootcss.com/twitter-bootstrap/3.0.3/css/bootstrap.min.css">
  <style>
    body {
      padding-top: 80px;
      background-color: #eee;
    }
    .form-panel{
      max-width: 330px;
      margin: 0 auto;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="panel panel-primary form-panel">
      <div class="panel-heading">百度登录测试页</div>
      <div class="panel-body">
        <form class="form-horizontal" role="form" method="post" action="index.php">
              <div class="form-group">
                  <label for="input_user_name" class="col-sm-3 control-label">用户名</label>
                  <div class="col-sm-9">
                    <input type="text" class="form-control" id="input_user_name" name="username" placeholder="用户名" value="<?php if(isset($username)) echo $username; ?>">
                  </div>
              </div>
              <div class="form-group">
                  <label for="input_password" class="col-sm-3 control-label">密码</label>
                  <div class="col-sm-9">
                    <input type="password" class="form-control" id="input_password" name="password" placeholder="密码" value="<?php if(isset($password)) echo $password; ?>">
                  </div>
              </div>
              <?php if(isset($need_vcode)){ ?>
              <div class="form-group">
                  <label for="input_vcode" class="col-sm-3 control-label">验证码</label>
                  <div class="col-sm-4">
                    <input type="text" class="form-control" id="input_vcode" name="vcode" placeholder="验证码">
                  </div>
                  <div class="col-sm-5">
                    <img src="<?= $result['data']['vcode_pic_url'] ?>" alt="">
                  </div>
              </div>
              <?php } ?>
              <div class="form-group">
                  <div class="col-sm-offset-3 col-sm-9">
                    <button type="submit" class="btn btn-primary btn-block">登录</button>
                  </div>
              </div>
        </form>
      </div>
    </div>
  </div>
  <script src="http://cdn.bootcss.com/jquery/1.10.2/jquery.min.js"></script>
  <script src="http://cdn.bootcss.com/twitter-bootstrap/3.0.3/js/bootstrap.min.js"></script>
</body>
</html>
