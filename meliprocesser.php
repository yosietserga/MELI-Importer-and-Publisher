<?php

error_reporting(0);
require_once('config.php');
require_once('src/Necoyoad/autoload.php');

$session = new Necoyoad_Session;
$request = new Necoyoad_Request;

$meli = new Necoyoad_Meli(MELI_APP_ID, MELI_APP_SECRET);
$meli->setRedirectUrl(MELI_REDIRECT_URL);

$result = $meli->initialize();
if ($request->hasPost('reportToEmail')) {
    $_SESSION['reportToEmail'] = $request->getPost('reportToEmail');
}

$importer = $meli->get_profiles(array('meli_id'=>$request->getPost('profileToImport')));

if (isset($importer[0]['meli_id']) && $request->hasQuery('import_products')) {
    $meli->set_refresh_token( $importer[0]['meli_refresh_token'] );
    $meli->set_token( $importer[0]['meli_token'] );
    $meli->set_expire( $importer[0]['meli_expire'] );
    $meli->set_code( $importer[0]['meli_code'] );
    $meli->set_meli_id( $importer[0]['meli_id'] );
    $meli->check_expire( $meli->get_expire() );
    $meli->import_products();
}

$exporter = $meli->get_profiles(array('meli_id'=>$request->getPost('profileToExport')));

if (isset($exporter[0]['meli_id']) && $request->hasQuery('publish_products')) {
    $meli->clear_refresh_token();
    $meli->clear_token();
    $meli->clear_expire();
    $meli->clear_code();
    $meli->clear_meli_id();

    $meli->set_refresh_token( $exporter[0]['meli_refresh_token'] );
    $meli->set_token( $exporter[0]['meli_token'] );
    $meli->set_expire( $exporter[0]['meli_expire'] );
    $meli->set_code( $exporter[0]['meli_code'] );
    $meli->set_meli_id( $exporter[0]['meli_id'] );

    $meli->check_expire( $meli->get_expire() );
    $total = $published = $error = 0;
    foreach ($meli->get_products(array('meli_id'=>$importer[0]['meli_id'])) as $product) {
        $data['title'] = $product['name'];
        $data['category_id'] = $product['meli_category_id'];
        $data['price'] = ((int)$product['price'] <= 0) ? 100 : (float)$product['price'];
        $data['currency_id'] = (is_null($product['currency_id'])) ? 'ARS' : $product['currency_id'];
        $data['available_quantity'] = ($product['available_quantity'] <= 0) ? 1 : $product['available_quantity'];
        $data['buying_mode'] = $product['buying_mode'];
        $data['listing_type_id'] = ($product['listing_type_id'] == 'free') ? 'bronze' : $product['listing_type_id'];
        $data['condition'] = $product['condition'];
        $data['description'] = $product['description'];

        if ($product['variations'])
            $data['variations'] = str_replace("\'","'",unserialize($product['variations']));

        $data['pictures'] = array(
            array('source'=>'http://rumat.com.ar/newapi/images/data/07-16/meli-118503685-14677759556325976.jpg')
        );
        $data['pictures'] = str_replace("\'","'",unserialize($product['image']));

        $result = $meli->publish_products($data);

        if ($result['error']) {
            $error_log .= "\n\r-------------------- SUCCESS -----------------------\n\r";
            $error_log .= serialize($result);
            $error_log .= "\n\r-------------------- /SUCCESS ----------------------\n\r";
            $error++;
        } else {
            $error_log = "";
            $published++;
        }
        $total++;
    }

    if (!empty($_SESSION['reportToEmail'])) {
        mail($_SESSION['reportToEmail'], "ML Importer",
            'Se intentaron publicar ' . $total .
            ' productos en el perfil ' . $meli->get_meli_id() .
            '. Se publicaron: ' . $published .
            ' y dieron error: ' . $error .
            '. MESSAGE: ' . $error_log);
    }
}

if ($request->hasQuery('getActivities')) {
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-Type: application/json');
    echo json_encode($meli->get_activities(array(
        'order'=>'DESC',
        'sort'=>'date_added',
        'limit'=>20
    )));
}

if ($request->hasQuery('deleteProfile') && $request->hasPost('id')) {
    $meli->delete_profile($request->getPost('id'));
}