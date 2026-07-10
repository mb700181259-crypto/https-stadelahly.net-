<?php
/* Copyrights (C) Arb4Host Network */
defined( 'ABSPATH' ) or die( 'No direct access allowed!' );

function a4h_template_dependency_required( $dependency ): void {
    ?>
    <!DOCTYPE HTML>
    <html dir="rtl">
    <head>
        <title><?php echo __( 'خطأ بإعدادات السيرفر المستضيف', THEME_TEXT_DOMAIN ); ?></title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
        <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"/>
        <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>
        <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/earlyaccess/droidarabickufi.css"/>
        <style>
            body {
                font: normal 16px/2 'Droid Arabic Kufi', Tahoma, Arial, sans-serif;
                background: #D8DBE1;
            }

            .license-wrapper {
                margin: 50px auto;
                max-width: 600px;
                background: #FFFFFF;
                box-shadow: 0 0 4px rgba(0, 0, 0, 0.1), 0 0 4px rgba(0, 0, 0, 0.2) inset;
                border: 1px solid #FFFFFF;
            }

            .header {
                padding: 20px;
                border-bottom: 1px solid #EEEEEE;
                text-align: center;
            }

            .header i {
                color: #BB0000;
            }

            .main {
                padding: 20px;
            }

            .footer {
                padding: 20px;
                border-top: 1px solid #EEEEEE;
                text-align: center;
            }

            .footer a {
                margin: 5px;
                display: inline-block;
                color: #FFFFFF !important;
            }

            .footer .validate_error {
                margin-top: 15px;
                border-radius: 4px;
                line-height: 45px;
                color: #860000;
                background-color: rgba(255, 0, 0, 0.36);
                text-shadow: 0 1px 5px #ffffff;
                display: none;
            }

            #loading-icon {
                display: none;
            }

            #loading-icon.visible {
                display: inline-block;
            }
        </style>
    </head>
    <body>
    <div class="license-wrapper">
        <header class="header">
            <h2><i class="fa fa fa-exclamation-triangle"></i>خطأ بالسيرفر أثناء تفعيل القالب</h2>
        </header>
        <main class="main">
            <p>فشل في تفعيل القالب للأسباب التالية:</p>
            <?php if ( $dependency === 'php' ): ?>
                <ul>
                    <li>أنت تستخدم إصدار قديم من <b>PHP</b>
                        <ul>
                            <li>
                                <div>يجب الإتصال بالمستضيف لترقية إصدار الـ <b>PHP</b> على موقعكم.</div>
                            </li>
                            <li>
                                <div>أقل إصدار مدعوم من <b>PHP</b> هو الإصدار PHP 8.1.x</div>
                            </li>
                            <li>
                                <div>المستضيف هو شركة الإستضافة Hosting التي تقوم بإستضافة موقعكم على سيرفراتها.</div>
                            </li>
                            <li>
                                <div>إذا كنت تعمل على سيرفر خاص، برجاء الإتصال بمسئول السيرفر لترقية إصدار الـ <b>PHP</b> على السيرفر.</div>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php elseif ( $dependency == 'ioncube' ): ?>
                <ul>
                    <li><b>IonCube Loader</b> غير مفعل على السيرفر
                        <ul>
                            <li>
                                <div>يجب الإتصال بالمستضيف لتفعيل <b>Ioncube Loader</b> على موقعكم.</div>
                            </li>
                            <li>
                                <div>المستضيف هو شركة الإستضافة Hosting التي تقوم بإستضافة موقعكم على سيرفراتها.</div>
                            </li>
                            <li>
                                <div>إذا كنت تعمل على سيرفر خاص، برجاء الإتصال بمسئول السيرفر لتفعيل <b>Ioncube Loader</b> على السيرفر.</div>
                            </li>
                            <li>
                                <div>ملحوظة: يجب تفعيل احدث اصدار من<b>Ioncube Loader</b> على السيرفر.</div>
                            </li>
                        </ul>
                    </li>
                </ul>
            <?php endif; ?>
        </main>
        <footer class="footer">
            <a href="https://cp.arb4host.net/submitticket.php?language=arabic" target="_blank" class="btn btn-secondary btn-success">الاتصال بالدعم الفني</a>
            <a href="<?php echo admin_url( 'themes.php' ); ?>" class="btn btn-secondary btn-danger">تفعيل قالب آخر لحين حل المشكلة</a>
        </footer>
    </div>
    </body>
    </html>
    <?php
}

function a4h_template_dependency_required_admin_notices() {
    ?>
    <style>
        .tempupdate {
            line-height: 23px;
            font-family: Tahoma, Arial, sans-serif
            direction: rtl;
            text-align: right;
        }
    </style>
    <div class='notice notice-error tempupdate is-dismissible'>
        <ul>
            <li>
                <div><b>خطأ بالسيرفر أثناء تفعيل القالب</b></div>
            </li>
            <li><b>IonCube Loader</b> غير مفعل على السيرفر</li>
            <li>
                <div>يجب الإتصال بالمستضيف لتفعيل <b>Ioncube Loader</b> على موقعكم.</div>
            </li>
            <li>
                <div>المستضيف هو شركة الإستضافة Hosting التي تقوم بإستضافة موقعكم على سيرفراتها.</div>
            </li>
            <li>
                <div>إذا كنت تعمل على سيرفر خاص، برجاء الإتصال بمسئول السيرفر لتفعيل <b>Ioncube Loader</b> على السيرفر.</div>
            </li>
        </ul>
    </div>
    <?php
}