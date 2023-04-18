<style>
    .header {
        display: flex;
        justify-content: space-between;
        flex-direction: row;
        align-items: center;
        width: 100%;
        background-color: #101010;
        padding: 20px;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .header-logo {
        height: 30px;
        user-select: none;
        pointer-events: none;
    }

    .header-signout-button {
        height: 30px;
        width: fit-content;
        color: #fff;
        background-color: #06f;
        border-radius: 15px;
        padding: 0 15px;
        border: unset;
        font-weight: bold;
        letter-spacing: 0.8px;
        cursor: pointer;
        outline: none;
        -webkit-tap-highlight-color: transparent;
    }

    .header-signout-button:hover {
        background-color: #05f;
    }

    .header-signout-button:active {
        background-color: #fff;
        color: #06f;
    }
</style>
<header class="header">
    <img class="header-logo" src="/resources/dewsense-logo.png" alt="DewSense">
    <?php if (\Core\Auth::isLoggedIn()) : ?>
        <form action="/signout" method="POST">
            <button class="header-signout-button" type="submit">Sign Out</button>
        </form>
    <?php endif; ?>
</header>