<style>
        .secondary-navbar {
            background-color: #f8f9fa;
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        #secondnav .navbar-nav {
        flex: 1;
        justify-content: center; /* Center the nav items */
    }
</style>


<nav class="navbar navbar-expand-lg navbar-light secondary-navbar" id="secondnav">
                    <div class="container-fluid">
                        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#secondaryNavbar" aria-controls="secondaryNavbar" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="secondaryNavbar">
                            <ul class="navbar-nav">
                                <li class="nav-item active">
                                    <a class="nav-link" href="index.php">Dashboard</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#">Subcriptions</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#">Payment history</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#">Settings</a>
                                </li>
                            </ul>
                        </div>
                    </div>
</nav>