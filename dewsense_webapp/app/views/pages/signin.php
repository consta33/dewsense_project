<!DOCTYPE html>
<html lang="en">

<head>
    <title>Sign In</title>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Dewsense Sign In Page">
    <link rel="canonical" href="https://dew-sense.com/signin/">
    <link rel="icon" href="/resources/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/css/base.css">
    <style>
        #signin-page {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .signin-form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            height: fit-content;
            width: 100%;
            max-width: 260px;
        }

        .signin-form-input-label {
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 260px;
            gap: 4px;
            color: #909090;
            font-size: 14px;
            letter-spacing: 0.4px;
            border: 4px solid #303030;
            padding: 4px;
            margin: 3px;
            border-radius: 18px;
            cursor: text;
        }

        .signin-form-credential-input {
            background-color: transparent;
            outline: unset;
            border: unset;
            color: #fff;
            height: fit-content;
            width: 100%;
            font-size: 17px;
            padding-left: 10px;
        }

        .signin-form-signin-button {
            height: 30px;
            width: fit-content;
            color: #fff;
            background-color: #06f;
            border-radius: 15px;
            padding: 0 15px;
            margin-top: 5px;
            border: unset;
            font-weight: bold;
            letter-spacing: 0.8px;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
            outline: none;
        }

        .signin-form-signin-button:hover {
            background-color: #05f;
        }

        .signin-form-signin-button:active {
            background-color: #fff;
            color: #06f;
        }

        input:-webkit-autofill {
            background-color: none !important;
            background-image: none !important;
        }
    </style>
</head>

<body>
    <?php \Core\Render::component("/header"); ?>
    <div id="signin-page" class="page-content">
        <form class="signin-form" method="POST" action="/signin">
            <label class="signin-form-input-label">
                <input class="signin-form-credential-input" type="text" name="username" required autocorrect="off" autocapitalize="none" spellcheck="false" autocomplete="off" placeholder="Username" maxlength="20" />
            </label>
            <label class="signin-form-input-label">
                <input class="signin-form-credential-input" type="password" name="password" required autocorrect="off" autocapitalize="none" spellcheck="false" autocomplete="off" placeholder="Password" maxlength="20" />
            </label>
            <button class="signin-form-signin-button" type="submit">Sign In</button>
        </form>
    </div>
    <?php \Core\Render::component("/footer"); ?>
    <script>
        // Prevent right click
        document.addEventListener(`contextmenu`, (event) => event.preventDefault())
    </script>
</body>

</html>