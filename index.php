<?php

error_reporting(0);
require_once('config.php');
require_once('src/Necoyoad/autoload.php');

$session = new Necoyoad_Session;
$request = new Necoyoad_Request;

$meli = new Necoyoad_Meli(MELI_APP_ID, MELI_APP_SECRET);
$meli->setRedirectUrl(MELI_REDIRECT_URL);

$result = $meli->index();

?>
<!doctype html>
<html class="no-js" lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Necoyoad Meli Manager</title>
    <link rel="stylesheet" href="css/foundation.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.0-rc.2/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="css/app.css">

</head>
<body>
<div class="row">
    <div class="large-12 columns">
        <h1 style="text-align:center"><img src="https://upload.wikimedia.org/wikipedia/commons/d/d4/MercadoLibre_logo.PNG" alt="MercadoLibre Manager" width="300" /></h1>
    </div>
</div>

<div class="row">
    <div class="large-12 columns">
        <div class="callout">
            <p>INSTRUCCIONES: Para importar y exportar productos autom[aticamente, los usuarios deben estar registrados en la aplicaci&oacute;n previamente. Para agregar nuevos usuarios haga click en el bot&oacute;n <i>Agregar Usuario</i></p>
            <div class="row">
                <div class="large-4 medium-4 columns">
                    <p>
                        <a href="index.php?action=add_profile&meli=1" class="button">Agregar Usuario</a>
                    </p>
                    <p>
                        <ul id="profilesWrapper">
                        <?php foreach ($meli->get_profiles() as $profile) { ?>
                        <?php if (empty($profile['meli_id'])) continue; ?>
                            <li data-meli_id="<?php echo $profile['meli_id']; ?>">
                                <button class="button" aria-label="Dismiss alert" type="button" onclick="deleteProfile('<?php echo $profile['meli_id']; ?>');" style="padding:0.5em" data-close>
                                    <span aria-hidden="true">&times;</span> Eliminar
                                </button>
                                <br />
                                <?php echo $profile['company']; ?><br />
                                <?php echo $profile['email']; ?>
                            </li>
                        <?php } ?>
                        </ul>
                    </p>
                </div>

                <div class="large-8 medium-8 columns">
                    <div class="large-6 medium-6 columns">
                        <p>
                            Arrastre hasta aqu&iacute; el perfil de donde desea <b>Descargar</b> productos
                        </p>
                        <div id="importerWrapper">

                            <ul>
                                <li class="placeholder"><img src="images/avatar01.jpg" alt="Download" /></li>
                            </ul>
                        </div>
                    </div>

                    <div class="large-6 medium-6 columns">
                        <p>
                            Arrastre hasta aqu&iacute; el perfil donde desea <b>Publicar</b> productos
                        </p>
                        <div id="exporterWrapper">
                            <ul>
                                <li class="placeholder"><img src="images/avatar02.jpg" alt="Upload" /></li>
                            </ul>
                        </div>
                    </div>


                    <div class="large-12 medium-12 columns">
                        <a class="button" style="width:100%" href="#" onclick="downloadUploadProfile();return false;">Importar / Exportar</a>
                        <br />

                        <input type="email" id="reportToEmail" name="reportToEmail" value="" placeholder="Ingresa el email a reportar" />
                    </div>
                </div>
            </div>

            <input type="hidden" value="" id="profileToImport" name="profileToImport" />
            <input type="hidden" value="" id="profileToExport" name="profileToExport" />

            <div class="row">
                <div class="large-12 medium-12 columns">
                <div id="activitiesWrapper">
                    <ul>
                        <li></li>
                    </ul>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="js/vendor/jquery.js"></script>
<script src="js/vendor/what-input.js"></script>
<script src="js/vendor/foundation.js"></script>
<script src="https://code.jquery.com/ui/1.12.0-rc.2/jquery-ui.min.js"></script>
<script src="js/app.js"></script>
</body>
</html>

