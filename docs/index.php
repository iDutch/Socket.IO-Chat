<?php
require('inc/MemcachedSessionHandler.php');
require('inc/DBAdapter.php');
require('../config/Config.php');
require('../config/DBConfig.php');
require('../config/MemcachedConfig.php');

$memcached = new Memcached;
$memcached->addServer(MemcachedConfig::MEMCACHED_HOST, MemcachedConfig::MEMCACHED_PORT);
$memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_PHP);
session_set_save_handler(new MemcachedSessionHandler($memcached));
session_start();

$DB = DBAdapter::getInstance('mysql:host=' . DBConfig::DB_HOST . ';port=' . DBConfig::DB_PORT . ';dbname=' . DBConfig::DB_NAME . ';charset=UTF8', DBConfig::DB_USER, DBConfig::DB_PASS);

if (isset($_POST['login'])) {
    $noempty = TRUE;
    if(empty($_POST['loginname'])) {
        $loginname_error = 'Please enter your email address or nickname.';
        $noempty = FALSE;
    }
    if(empty($_POST['password'])) {
        $password_error = 'Please enter your password.';
        $noempty = FALSE;
    }
    if ($noempty) {
        $result = $DB->select('SELECT id, nickname FROM users WHERE (email = :email OR nickname = :nickname) AND password = :password', array(
            'email' => $_POST['loginname'],
            'nickname' => $_POST['loginname'],
            'password' => hash('sha256', $_POST['password'].Config::SALT),
        ));
        if(count($result)) {
            $_SESSION['user_id'] = $result[0]->id;
            $_SESSION['nickname'] = $result[0]->nickname;
        } else {
            $login_error = 'Invalid email address/nickname or password entered! Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="">
        <meta name="author" content="">

        <title>Hoogstraaten.eu</title>

        <!-- Bootstrap core CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Custom styles for this template -->
        <link href="css/jumbotron-narrow.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
          <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body>
        <div class="container">
            <div class="header">
                <nav>
                    <ul class="nav nav-pills pull-right">
                        <li role="presentation" ><a href="#">Home</a></li>
                        <li role="presentation" class="active"><a href="#">NodeJS/Socket.IO Chat</a></li>
                        <li role="presentation"><a href="#">Contact</a></li>
                    </ul>
                </nav>
                <h3 class="text-muted">Hoogstraaten.eu</h3>
            </div>
            <?php if (!isset($_SESSION['user_id'])) { ?>
                <div id="nickname-form">
                    <h3>Login</h3>
                    <form id="nickname-form" action="index.php" class="form-group" role="form" method="post">
                        <div class="form-group<?php echo (isset($loginname_error) || isset($login_error) ? ' has-error' : ''); ?>">
                            <?php if (isset($loginname_error)) { ?>
                                <label class="control-label" for="loginname"><?php echo $loginname_error; ?></label>
                            <?php } ?>
                            <?php if (isset($login_error)) { ?>
                                <label class="control-label" for="loginname"><?php echo $login_error; ?></label>
                            <?php } ?>
                                <input name="loginname" type="text" class="form-control" value="<?php echo (isset($_POST['loginname']) ? $_POST['loginname'] : ''); ?>" placeholder="Email address or nickname">
                        </div>
                        <div class="form-group<?php echo (isset($password_error) ? ' has-error' : ''); ?>">
                            <?php if (isset($password_error)) { ?>
                                <label class="control-label" for="nickname"><?php echo $password_error; ?></label>
                            <?php } ?>
                            <input name="password" type="password" class="form-control" placeholder="Password">
                        </div>
                        <div class="form-group">
                            <button type="submit" name="login" class="btn btn-primary pull-right">Sign in</button>
                            <div class="clearfix"></div>
                        </div>
                    </form>
                </div>
            <?php } else { ?>
                <div class="row">
                    <div class="col-xs-3">
                        <ul id="userlist" class="list-unstyled"></ul>
                    </div>
                    <div id="chatbox" class="col-xs-9">
                        <div id="messages" class="well" style="height: 300px; overflow-y: auto;">
                            <ul class="list-unstyled"></ul>
                        </div>

                        <div class="row">
                            <form id="chatbox-form" class="form-group" role="form">
                                <div class="form-group col-xs-9">
                                    <input type="text" class="form-control" id="m" placeholder="Message">
                                </div>
                                <div class="form-group col-xs-3">
                                    <button type="submit" class="btn btn-primary pull-right">Send</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <footer class="footer">
                <p>&copy; Hoogstraaten.eu 2014</p>
            </footer>
        </div> <!-- /container -->


        <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <!-- IE8 extend Data.prototype with toISOstring method -->
        <script src="js/ie8-date-to-iso-string-workaround.js"></script>
        <script src="js/timeago.js"></script>
        <script src="js/socket.io.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <script>
            $(document).ready(function () {
                $(".timeago").timeago();
                var socket = io('http://hoogstraaten.eu:3000');
                $('form#chatbox-form').submit(function () {
                    var message = $('#m').val();
                    if (message.length > 0) {
                        socket.emit('chat message', message);
                        var D = new Date();
                        var $message = $('<li><strong>Me</strong>: ' + message + '<i><time class="timeago pull-right" datetime="' + D.toISOString() + '"></time></i></li>')
                        $(".timeago", $message).timeago();
                        $("#messages ul").append($message);
                        $("#messages").animate({scrollTop: $("#messages")[0].scrollHeight}, 1000);
                        $("#m").val('');
                    }
                    return false;
                });
                socket.on('chat message', function (msg) {
                    var D = new Date();
                    var $message = $('<li>' + msg + '<i><time class="timeago pull-right" datetime="' + D.toISOString() + '"></time></i></li>')
                    $(".timeago", $message).timeago();
                    $("#messages ul").append($message);
                    $("#messages").animate({scrollTop: $("#messages")[0].scrollHeight}, 100);
                });
                socket.on('userlist', function (users) {
                    $('#userlist').html('');
                    $.each(users, function (index, user) {
                        $('#userlist').append('<li><span class="label label-primary">' + user.nickname + '</span></li>');
                    });
                });
            });
        </script>
        <script src="js/ie10-viewport-bug-workaround.js"></script>
    </body>
</html>
