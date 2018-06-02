<?php

if (!file_exists('../../config.php')) {
    header('Location: ../../install.php');
    die;
}

require_once('../../config.php');
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');

foreach (glob("models/*.php") as $class_name) {
    include($class_name);
}

// required headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

/**
 * @SWG\Swagger(
 *    @SWG\Info(
 *       title="Moodle API",
 *       version="1.0.0"
 *    ),
 *     schemes={"http"},
 *     host=API_HOST,
 *     basePath="/api/v1"
 * )
 * @SWG\Get(
 *   path="/test.php",
 *   summary="list products",
 *   produces={"application/json"},
 *   parameters={},
 *   @SWG\Response(
 *      response=200,
 *      description="A list with products.",
 *      @SWG\Schema(
 *          type="array",
 *          @SWG\Items(
 *              ref="#/definitions/Product"
 *          )
 *      )
 *   ),
 *   @SWG\Response(
 *      response="default",
 *      description="an ""unexpected"" error"
 *   )
 * )
 */

// products array
$products_arr=array();
$products_arr["records"]=array();

$i = 1;
while ($i <= 5) {

    $product_item = new Product();
    $product_item->id = $i++;
    $product_item->name = 'test';
    $product_item->description = html_entity_decode('توضیحات');
    $product_item->price = 1000;

    array_push($products_arr["records"], $product_item);
}

echo json_encode($products_arr);

?>
