<div class="container">
    <div class="auth-content">
        <form action="<?=  $action ?>" method="post">
            <h2><?= $title ?></h2>

            <?php if(isset($showUsername) && $showUsername): ?>
                <label for="username">Username</label>
                <input type="text" name="username" placeholder="Enter Username" required>
            <?php endif; ?>

                <label for="password">Password</label>
                <input type="password" name="password" placeholder="Enter Password" required>

                <button type="submit"><?= $button ?></button>

                <?php if ($title === "Login"): ?>
                <p>Don't have an account? <a href="register.php">Register</a></p>
                <?php else: ?>
                    <p>Already have an account? <a href="login.php">Login</a></p>
                <?php endif; ?>
        </form>
    </div>
</div>
