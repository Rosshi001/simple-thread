<?php
    /* データベース設定 */
    $dsn = 'mysql:dbname=データベース名;host=localhost';
    $user = 'ユーザ名';
    $password = 'パスワード';
    $pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

    // 【！このSQLはテーブルを削除します！】
    if(isset($_POST["re"]) && $_POST["re"] != ''){
        if($_POST["re_password"] == "*********"){
            $sql = 'DROP TABLE format_table';
            $stmt = $pdo->query($sql);
        }
        else echo "違います";
    }


    /* 投稿用データベース(テキストファイルの代わり) */
    $sql = "CREATE TABLE IF NOT EXISTS format_table" //format_tableが存在しなければテーブル作成
	." ("
    . "id INT AUTO_INCREMENT PRIMARY KEY,"
	. "name char(32),"
    . "comment TEXT,"
    . "date TEXT,"
    . "submit_password TEXT"
	.");";
    $stmt = $pdo->query($sql);
    

    /* テーブル表示 
    $sql ='SHOW TABLES';
	$result = $pdo -> query($sql);
	foreach ($result as $row){
		echo $row[0];
		echo '<br>';
	}
    echo "<hr>";
    */


    /* nameとcommentとsubmit_passwordとsubmitのvalueの初期設定（通常時） */
    $print_name = null;
    $print_comment = null;
    $print_password = null;
    $print_submit = "投稿する";

    /* 編集フラグ */
    $edit_flag_id = 0; //編集フラグ（１以上のとき投稿フォームが編集投稿フォームになる）


    /* 投稿文追加 */
    if ((isset($_POST["name"]) && $_POST["name"] != '') &&                          //名前が入力されていてかつ
        (isset($_POST["comment"]) && $_POST["comment"] != '') &&                    //コメントが入力されていてかつ
        (isset($_POST["submit_password"]) && $_POST["submit_password"] != '') &&    //パスワードが入力されていてかつ
        (isset($_POST["submit"]) && $_POST["submit"] != '') &&                      //投稿ボタンがクリックされていてかつ 
        ($_POST["edit_flag_id"] == 0))                                             //編集フラグが立っていないなら
    {
        //echo "投稿文追加開始<br><br>"; //デバッグ用

        $sql = $pdo -> prepare("INSERT INTO format_table (name, comment, date, submit_password) VALUES (:name, :comment, :date, :submit_password)");
        //パラメータの紐づけ
        $sql -> bindParam(':name', $name, PDO::PARAM_STR);  
        $sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
        $sql -> bindParam(':date', $date, PDO::PARAM_STR);
        $sql -> bindParam(':submit_password', $submit_password, PDO::PARAM_STR);

        $name = $_POST["name"];
        $comment = $_POST["comment"];
        $date = date("Y/m/d H:i:s");
        $submit_password = $_POST["submit_password"];

        $sql -> execute();  //ここでテーブルに追加する
        echo "投稿文追加完了<br><br>"; 
    }
    else if(((isset($_POST["name"]) && $_POST["name"] != '') ||                         //名前が入力されているか
             (isset($_POST["comment"]) && $_POST["comment"] != '') ||                   //コメントが入力されているか
             (isset($_POST["submit_password"]) && $_POST["submit_password"] != '')) &&  //パスワードが入力されていてかつ
            (isset($_POST["submit"]) && $_POST["submit"] != '') &&                      //投稿ボタンがクリックされていてかつ
            ($_POST["edit_flag_id"] == 0))                                             //編集フラグが立っていないなら
        echo "エラー：名前・コメント・パスワード全てに入力を行ってください。<br><br>";

    /* 投稿文削除機能 */
    if ((isset($_POST["delete_id"]) && $_POST["delete_id"] != '') &&                //削除IDが入力されていてかつ
        (isset($_POST["delete_password"]) && $_POST["delete_password"] != '') &&    //パスワードが入力されていてかつ
        (isset($_POST["delete"]) && $_POST["delete"] != ''))                        //削除ボタンがクリックされていたら
    {
        //echo "投稿文削除開始<br><br>"; //デバッグ用
        
        $id = $_POST["delete_id"];
        $delete_password = $_POST["delete_password"];
        //削除IDのパスワードを取り出す
        $sql = 'SELECT * FROM format_table WHERE id=:id ';
        $stmt = $pdo->prepare($sql);                  // ←差し替えるパラメータを含めて記述したSQLを準備し、
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); // ←その差し替えるパラメータの値を指定してから、
        $stmt->execute();                             // ←SQLを実行する。
        $result = $stmt->fetchAll();
        
        if($result[0]["submit_password"] == $delete_password){
            $sql = 'delete from format_table where id=:id';
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            echo "投稿文削除完了<br><br>"; 
        }
        else if($result[0]["submit_password"] != $delete_password)
            echo "エラー：パスワード不一致<br><br>";
    }
    else if(((isset($_POST["delete_id"]) && $_POST["delete_id"] != '') ||                //削除IDが入力されているか
             (isset($_POST["delete_password"]) && $_POST["delete_password"] != '')) &&   //パスワードが入力されていてかつ
            (isset($_POST["delete"]) && $_POST["delete"] != ''))                         //削除ボタンがクリックされていたら
        echo "エラー：削除ID・パスワード全てに入力を行ってください<br><br>";
    

    /* 投稿文編集機能 */
    /* 編集フラグを立て、編集箇所を取り出す */
    if ((isset($_POST["edit_id"]) && $_POST["edit_id"] != '') &&                //編集IDが入力されていてかつ
        (isset($_POST["edit_password"]) && $_POST["edit_password"] != '') &&    //パスワードが入力されていてかつ
        (isset($_POST["edit"]) && $_POST["edit"] != ''))                        //削除ボタンがクリックされていたら
    {
        //echo "投稿文編集フラグ<br><br>"; //デバッグ用
        
        $id = $_POST["edit_id"];
        $edit_password = $_POST["edit_password"];
        //編集IDのパスワードを取り出す
        $sql = 'SELECT * FROM format_table WHERE id=:id ';
        $stmt = $pdo->prepare($sql);                  // ←差し替えるパラメータを含めて記述したSQLを準備し、
        $stmt->bindParam(':id', $id, PDO::PARAM_INT); // ←その差し替えるパラメータの値を指定してから、
        $stmt->execute();                             // ←SQLを実行する。
        $result = $stmt->fetchAll();
        
        if($result[0]["submit_password"] == $edit_password){
            //編集フラグを立てる
            $edit_flag_id = $id; 
            $print_name = $result[0]["name"];
            $print_comment = $result[0]["comment"];
            $print_password = $result[0]["submit_password"];
            $print_submit = "編集完了";
            
            echo "編集対象投稿文呼出完了<br><br>";
        }
        else if($result[0]["submit_password"] != $edit_password)
            echo "エラー：パスワード不一致<br><br>";
    }
    else if(((isset($_POST["edit_id"]) && $_POST["edit_id"] != '') ||                //編集IDが入力されているか
             (isset($_POST["edit_password"]) && $_POST["edit_password"] != '')) &&   //パスワードが入力されていてかつ
            (isset($_POST["edit"]) && $_POST["edit"] != ''))                         //編集ボタンがクリックされていたら
        echo "エラー：編集ID・パスワード全てに入力を行ってください<br><br>";

    /* 編集文投稿 */
    if ((isset($_POST["name"]) && $_POST["name"] != '') &&                          //名前が入力されていてかつ
        (isset($_POST["comment"]) && $_POST["comment"] != '') &&                    //コメントが入力されていてかつ
        (isset($_POST["submit_password"]) && $_POST["submit_password"] != '') &&    //パスワードが入力されていてかつ
        (isset($_POST["submit"]) && $_POST["submit"] != '') &&                      //投稿ボタンがクリックされていてかつ 
        ($_POST["edit_flag_id"] >= 1))                                             //編集フラグが立っていたなら
    {
        //echo "投稿文編集開始<br><br>"; //デバッグ用

        $id = $_POST["edit_flag_id"];

        $name = $_POST["name"];
        $comment = $_POST["comment"];
        $date = date("Y/m/d H:i:s");
        $submit_password = $_POST["submit_password"];
        $sql = 'UPDATE format_table SET name=:name,comment=:comment,date=:date,submit_password=:submit_password WHERE id=:id';
        //パラメータの紐づけ
        $stmt = $pdo->prepare($sql); 
        $stmt -> bindParam(':name', $name, PDO::PARAM_STR);  
        $stmt -> bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt -> bindParam(':date', $date, PDO::PARAM_STR);
        $stmt -> bindParam(':submit_password', $submit_password, PDO::PARAM_STR);
        $stmt -> bindParam(':id', $id, PDO::PARAM_INT);  

        $stmt -> execute();
        echo "投稿文編集完了<br><br>";
    }
    else if(((isset($_POST["name"]) && $_POST["name"] != '') ||                         //名前が入力されているか
             (isset($_POST["comment"]) && $_POST["comment"] != '') ||                   //コメントが入力されているか
             (isset($_POST["submit_password"]) && $_POST["submit_password"] != '')) &&  //パスワードが入力されていてかつ
            (isset($_POST["submit"]) && $_POST["submit"] != '') &&                      //投稿ボタンがクリックされていてかつ
            ($_POST["edit_flag_id"] >= 1))                                              //編集フラグが立っていないなら
        echo "エラー：名前・コメント・パスワード全てに入力を行ってください。<br><br>"; 
?>

<!DOCTYPE HTML>
<html>
<head>
    
    <meta charset = "utf-8">
    <title>ミッション5-1掲示板</title>
    
</head>


<body>

    <div style = text-align:center;>
    <h1>掲示板　とりあえずなんか書いとけ( ' x ' )ｺｯﾁﾐﾝﾅ</h1>
    <h3>-デバッグにご協力お願いします-<br>
        (例)名前だけ入力して投稿ボタンを押す
    </h3>
    </div>
    <br>

    <form style = text-align:center; method = "post" action = "">
        投稿フォーム<br>
        <input type = "text" name = "name" placeholder = "名前" value = <?php echo $print_name; ?>>
        <input type = "text" name = "comment" placeholder = "コメント" value = <?php echo$print_comment; ?>>
        <input type = "text" name = "submit_password" placeholder = "パスワード" value = <?php echo $print_password; ?>>
        <input type = "submit" name = "submit" value = <?php echo $print_submit; ?>>
        <br><br>
        削除フォーム<br>
        <input type = "number" name = "delete_id" placeholder = "削除ID（半角）" value = "">
        <input type = "text" name = "delete_password" placeholder = "パスワード" value = "">
        <input type = "submit" name = "delete" value = "削除する">
        <br><br>
        編集フォーム<br>
        <input type = "number" name = "edit_id" placeholder = "編集ID（半角）" value = "">
        <input type = "text" name = "edit_password" placeholder = "パスワード" value = "">
        <input type = "submit" name = "edit" value = "編集する">
        <input type = "hidden" name = "edit_flag_id" value = <?php echo $edit_flag_id; ?>>
        <br><br>
        管理者用<br>
        <input type = "text" name = "re_password">
        <input type = "submit" name = "re">
        <br><br>
    </form>
    
    <hr>
    <h3>投稿文表示</h3><br>
    左から投稿ID、名前、コメント、投稿日時の順で表示されています
    <br><hr>
    <!--テーブルの内容表示-->
    <?php

        $sql = 'SELECT * FROM format_table';
        $stmt = $pdo->query($sql);
        $results = $stmt->fetchAll();
        foreach ($results as $row){
            //$rowの中にはテーブルのカラム名が入る
            echo $row['id'].'　'.
                 $row['name'].'　'.
                 $row['comment'].'　'.
                 $row['date'].'　'.'<br>';
            //echo $row['submit_password'].'<br>'; //デバッグ用
            echo "<hr>";
        }

    ?>

</body>
</html>