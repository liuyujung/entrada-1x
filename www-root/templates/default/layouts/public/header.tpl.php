<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
    <head>
        <meta charset="<?php echo DEFAULT_CHARSET; ?>" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta http-equiv="X-Frame-Options" content="SAMEORIGIN" />
        <title>%TITLE%</title>

        <meta name="description" content="%DESCRIPTION%" />
        <meta name="keywords" content="%KEYWORDS%" />

        <meta name="robots" content="index, follow" />

        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/bootstrap.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/font-awesome/css/font-awesome.min.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/print.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="print" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/common.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />
        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/css/style.css?release=<?php echo html_encode(APPLICATION_VERSION); ?>" rel="stylesheet" media="all" />

        <link href="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/favicon.ico" rel="shortcut icon" type="image/x-icon" />
        <script>
            if (self !== top) {
                top.location = self.location;
            }
        </script>
        <link href="<?php echo ENTRADA_RELATIVE; ?>/css/jquery/jquery-ui.css" rel="stylesheet" />

        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/jquery/jquery-ui.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script>jQuery.noConflict();</script>
        %JQUERY%

        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/scriptaculous/prototype.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/scriptaculous/scriptaculous.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/livepipe.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/window.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/livepipe/selectmultiplemod.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/common.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo ENTRADA_RELATIVE; ?>/javascript/selectmenu.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>

        <script src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/bootstrap.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        <script src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/js/libs/modernizr-2.5.3.min.js?release=<?php echo html_encode(APPLICATION_VERSION); ?>"></script>
        %HEAD%
    </head>
    <body>
        <header id="main-header">
            <div class="banner">
                <div class="container">
                    <div class="row-fluid">
                        <div class="span5">
                            <a href="<?php echo ENTRADA_URL; ?>"><img src="<?php echo $ENTRADA_TEMPLATE->relative(); ?>/images/logo.png" width="294" height="46" alt="<?php echo APPLICATION_NAME; ?>" title="<?php echo APPLICATION_NAME; ?>"/></a>
                        </div>
                        <?php
                        if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
                            ?>
                            <div class="span5">
                                <div class="welcome-area">
                                    <div class="userAvatar">
                                        <a href="#"><?php echo "<img src=\"".webservice_url("photo", array($ENTRADA_USER->getID(), (isset($uploaded_file_active) && $uploaded_file_active ? "upload" : (!file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-official") && file_exists(STORAGE_USER_PHOTOS."/".$ENTRADA_USER->getID()."-upload") ? "upload" : "official"))))."\" width=\"32\" height=\"42\" style=\"width: 32px; height: 42px;\" alt=\"".html_encode($_SESSION["details"]["firstname"]." ".$_SESSION["details"]["lastname"])."\" class=\"img-polaroid\" />"; ?></a>
                                    </div>
                                    <div class="welcome-block">
                                        Welcome <span class="userName"><?php echo $ENTRADA_USER->getFirstname() . " " . $ENTRADA_USER->getLastname(); ?></span>
                                        <br />
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/profile">My Profile</a> |
                                        <a href="<?php echo ENTRADA_RELATIVE; ?>/evaluations">My Evaluations</a>
                                        <?php
                                        /**
                                         * Cache any outstanding evaluations.
                                         */
                                        if (!isset($ENTRADA_CACHE) || !$ENTRADA_CACHE->test("evaluations_outstanding_"  . AUTH_APP_ID . "_" . $ENTRADA_USER->getID())) {
                                            $evaluations_outstanding = Models_Evaluation::getOutstandingEvaluations($ENTRADA_USER->getID(), $ENTRADA_USER->getActiveOrganisation(), true);

                                            if (isset($ENTRADA_CACHE)) {
                                                $ENTRADA_CACHE->save($evaluations_outstanding, "evaluations_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
                                            }
                                        } else {
                                            $evaluations_outstanding = $ENTRADA_CACHE->load("evaluations_outstanding_" . AUTH_APP_ID . "_" . $ENTRADA_USER->getID());
                                        }

                                        if ($evaluations_outstanding) {
                                            echo "<span class=\"badge badge-success\"><small>".$evaluations_outstanding."</small></span>";
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="span2">
                                <a href="<?php echo ENTRADA_RELATIVE; ?>/?action=logout" class="log-out">Logout <i class="icon icon-logout"></i></a>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>

            <?php
            if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
                ?>
                <div class="navbar">
                    <div class="navbar-inner">
                        <div class="container no-printing">
                            <?php echo navigator_tabs(); ?>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </header>
        <div class="container" id="page">
            <div class="row-fluid">
                <?php
                if ((isset($_SESSION["isAuthorized"])) && ($_SESSION["isAuthorized"])) {
                    ?>
                    <div class="span3 no-printing" id="sidebar">%SIDEBAR%</div>
                    <div class="span9" id="content">
                    <?php
                } else {
                    ?>
                    <div class="span12" id="content">
                    <?php
                }
                ?>
                <div class="clearfix inner-content">
<!--                    <div class="clearfix">%BREADCRUMB%</div>-->
