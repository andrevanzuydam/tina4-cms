<?php

/**
 * File browser
 *
 */
\Tina4\Get::add("/cms/file-browser",function (\Tina4\Response $response, \Tina4\Request $request) {
    $files = \Tina4\Utility::iterateDirectory("./uploads", "", "onclick=\"previewFile($(this).attr('file-data'))\"");
    return $response(\Tina4\renderTemplate("admin/file-browser.twig", ["files" => $files]));
});

\Tina4\Post::add("/cms/upload", function (\Tina4\Response $response, \Tina4\Request $request) {

    //Add the image to a nice path
    $imageFolder = "./uploads/".date("Y")."/".date("F");
    if (! file_exists($imageFolder) && !mkdir($imageFolder, 0777, true) && !is_dir($imageFolder)) {
        //throw new \RuntimeException(sprintf('Directory "%s" was not created', $imageFolder));
        return $response(["location" => "error creating folder"]);
    }
    $temp = current($_FILES);

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return null;
    }

    // Verify extension
    if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png"))) {
        header("HTTP/1.1 400 Invalid extension.");
        return null;
    }

    // Accept upload if there was no origin, or if it is an accepted origin
    $fileToWrite = $imageFolder . "/". $temp['name'];
    move_uploaded_file($temp['tmp_name'], $fileToWrite);
    return $response (["location" => str_replace("./uploads", "", $fileToWrite)]);
});


\Tina4\Get::add("/cms/login", function (\Tina4\Response $response) {
    $users = (new Users())->select("count(id) as number")->asArray();
    $twigNameSpace = (new Content())->getTwigNameSpace();

    if ((int)$users[0]["number"] === 0) {
        return $response(\Tina4\renderTemplate($twigNameSpace."/admin/setup.twig", ["twigNameSpace" => $twigNameSpace]));
    } else {
        return $response(\Tina4\renderTemplate($twigNameSpace."/admin/login.twig", ["twigNameSpace" => $twigNameSpace]));
    }
});

\Tina4\Get::add("/cms/login/reset", function (\Tina4\Response $response) {
    $users = (new Users())->select("count(id) as number")->asObject()[0];
    $twigNameSpace = (new Content())->getTwigNameSpace();
    if ($users->number === 0) {
        return $response(\Tina4\renderTemplate($twigNameSpace."/admin/setup.twig", ["twigNameSpace" => $twigNameSpace]));
    } else {
        return $response(\Tina4\renderTemplate($twigNameSpace."/admin/reset.twig", ["twigNameSpace" => $twigNameSpace]));
    }
});

\Tina4\Get::add("/cms/dashboard", function (\Tina4\Response $response) {
    $users = (new Users())->select("count(id) as number");
    $twigNameSpace = (new Content())->getTwigNameSpace();
    if (empty($users)) {
        return $response(\Tina4\renderTemplate($twigNameSpace."/admin/setup.twig", ["twigNameSpace" => $twigNameSpace]));
    } else {
        $menuItems = (new Content())->getCmsMenus();
        return $response(\Tina4\renderTemplate($twigNameSpace."/admin/dashboard.twig", ["menuItems" => $menuItems , "twigNameSpace" => $twigNameSpace]));
    }
});



\Tina4\Post::add("/cms/login", function (\Tina4\Response $response, \Tina4\Request $request) {
    if (!empty($request->params["confirmPassword"])) {
        $user = new Users($request->params);

        if (!$user->load("email = '{$request->params["email"]}'")) {
            $user->isActive = 1;
            $user->password = password_hash($user->password, PASSWORD_BCRYPT);
            $user->save();
            \Tina4\redirect("/cms/dashboard");
        } else {

            \Tina4\redirect("/cms/login");
        }
    } else {
        $user = new Users();
        //perform login
        if ($user->load("email = '{$request->params["email"]}'")) {
            if (password_verify($request->params["password"],$user->password)) {
                $_SESSION["user"] = $user->asArray();
                \Tina4\redirect("/cms/dashboard");
            } else {
                \Tina4\redirect("/cms/login?error=true");
            }
        } else {
            \Tina4\redirect("/cms/login?error=true");
        }
    }
    return null;
});

\Tina4\Get::add("/cms/logout", function (\Tina4\Response $response, \Tina4\Request $request) {
    session_destroy();
    session_write_close();

    \Tina4\redirect("/cms/login");

    return null;
});

