<?php
namespace BaseComposition;

$error = NULL;
$message = NULL;

if (isset($_GET['product_id'])) {
    $compo  = \Pasteque\CompositionsService::get($_GET['product_id']);
    $product = \Pasteque\ProductsService::get($compo->id);
    $vatprice = "";
    $price = $product->price_sell;
} else {
    $vatprice = "";
    $price = "";
    $product = NULL;
}

if (isset($_POST['inputData'])) {

    $objJSON = json_decode($_POST['inputData']);
    $compo = \Pasteque\CompositionsService::maj($objJSON);
    $product = $compo;
    if (!$product) {
        $err = \Pasteque\CompositionsService::errorInfo();
        foreach($err as $errmess) {
            $error[] = \i18n($errmess[0], PLUGIN_NAME, $errmess[1]);
        }
    }
}

$categories = \Pasteque\CategoriesService::getAll();
$products = \Pasteque\ProductsService::getAll(TRUE);
$modules = \Pasteque\get_loaded_modules(\Pasteque\get_user_id());
$taxes = \Pasteque\TaxesService::getAll();

$stocks = FALSE;
$discounts = FALSE;
if (in_array("base_stocks", $modules)) {
    $stocks = TRUE;
}
if (in_array("product_discounts", $modules)) {
    $discounts = TRUE;
}


/** write a button representing the category whith action javascript
 * @param $category an object Category show
 * @param $js an function javascript we want to execute onClick */
function catalog_category($category, $js) {
    echo "<a id=\"category-" . $category->id . "\" class=\"catalog-category\" onClick=\"javascript:" . $js . "return false;\">";
    echo "<img src=\"?" . \Pasteque\URL_ACTION_PARAM . "=img&w=category&id=" . $category->id . "\" />";
    echo "<p>" . $category->label . "</p>";
    echo "</a>";
}

\Pasteque\tpl_msg_box($message, $error);
?>

<h1><?php \pi18n('Composition edit',PLUGIN_NAME)?></h1>

<?php if (isset($product)) { ?>
    <form method='post' action="<?php echo \Pasteque\get_module_url_action(PLUGIN_NAME, 'composition');?>">
        <input type="hidden" value="<?php echo $product->id?>" name="delete-comp">
        <input type='submit' class='btn-delete' value='<?php \pi18n('Delete this composition',PLUGIN_NAME);?>'/>
    </form>
<?php } ?>

<form class='edit' id='data-compo' onsubmit='return false;'>
<div>
    <div id='composition' class='row'>
    <fieldset>
        <legend>Composition</legend>
        <?php \Pasteque\form_hidden("edit", $product, "id"); ?>
        <fieldset>
        <legend><?php \pi18n("Display", PLUGIN_NAME); ?></legend>
        <?php \Pasteque\form_input("edit", "Product", $product, "label", "string", array("required" => true)); ?>
        <?php \Pasteque\form_input("edit", "Product", $product, "visible", "boolean"); ?>
        <?php \Pasteque\form_input("edit", "Product", $product, "disp_order", "numeric"); ?>
        </fieldset>
        <fieldset>
        <legend><?php \pi18n("Price", PLUGIN_NAME); ?></legend>
        <?php \Pasteque\form_input("edit", "Product", $product, "tax_cat", "pick", array("model" => "TaxCategory")); ?>
        <div class="row">
            <label for="sellvat"><?php \pi18n("Sell price + taxes", PLUGIN_NAME); ?></label>
            <input id="sellvat" type="numeric" name="selltax" value="<?php echo $vatprice; ?>" />
        </div>
        <div class="row">
            <label for="sell"><?php \pi18n("Product.price_sell"); ?></label>
            <input type="hidden" id="realsell" name="realsell" <?php if ($product != NULL) echo 'value="' . $product->price_sell. '"'; ?> />
            <input id="sell" type="numeric" name="sell" value="<?php echo $price; ?>" />
        </div>
        <?php \Pasteque\form_input("edit", "Product", $product, "price_buy", "numeric"); ?>
        <div class="row">
            <label for="margin"><?php \pi18n("Margin", PLUGIN_NAME); ?></label>
            <input id="margin" type="numeric" disabled="true" />
        </div>
        <?php if ($discounts) {
            \Pasteque\form_input("edit", "Product", $product, "discount_enabled", "boolean", array("default" => FALSE));
            \Pasteque\form_input("edit", "Product", $product, "discount_rate", "numeric");
            } ?>
        </fieldset>
        <fieldset>
            <legend><?php \pi18n("Referencing", PLUGIN_NAME); ?></legend>
            <?php \Pasteque\form_input("edit", "Product", $product, "reference", "string", array("required" => true)); ?>
            <div class="row">
                <label for="barcode"><?php \pi18n("Product.barcode"); ?></label>
                <div style="display:inline-block; max-width:65%;">
                    <img id="barcodeImg" src="" />
                    <input id="barcode" type="text" name="barcode" <?php if ($product != NULL) echo 'value="' . $product->barcode . '"'; ?> />
                    <a class="btn" href="" onClick="javascript:generateBarcode(); return false;"><?php \pi18n("Generate"); ?></a>
                </div>
            </div>
        </fieldset>
        <?php \Pasteque\tpl_js_btn("btn", "addCmp()", "add_cmp");?>
    </fieldset>


    </div>
    <div id='subGroup'>
    <fieldset>
    <legend><?php \pi18n('SubGroups',PLUGIN_NAME); ?></legend>
        <div class='row'>
            <label for='edit-sgName'><?php pi18n('Name', PLUGIN_NAME); ?>:</label>
            <input type='text' id='edit-sgName'/>
        </div>
        <div class="row">
            <label for="edit-sgOrder">Order</label>
            <input id="edit-sgOrder" type="numeric" name="disp_order" value='0'>
        </div>
        <div class="row">
            <?php \Pasteque\tpl_js_btn("btn", "addSubGroup()", "add_sg");?>
        </div>

         <select id="listSubGr" onchange="showSubgroup()">
        </select>

        <input type='text' id='edit-sgNewName' placeholder='<?php pi18n('Rename subgroup', PLUGIN_NAME); ?>'/>
        <?php \Pasteque\tpl_js_btn("btn-delete", "delSubgroup()", "del_sg");?>

        <div>
            <div id='product-sub-container' class="product-container"></div>
        </div>
    </fieldset>
    </div>
    <!-- to change -->
    <div id='product' class='row'>
        <fieldSet>
            <legend><?php \pi18n('Product',PLUGIN_NAME); ?></legend>
            <div class="catalog-categories-container">
                <?php // add all category to div catalog-categories-container 
                foreach ($categories as $category) {
                    catalog_category($category, "changeCategory('" . $category->id . "');");
                }
                ?>
            </div>
            <div id="products" class="catalog-products-container"></div>
            <div class='row' id='btnAddAllPrd'>
                <input type='button' onclick='javascript:addAllPrd()' value='<?php pi18n('Add all product of the category:',PLUGIN_NAME)?>'>
            </div>
        </fieldSet>
    </div>
</div>
</form>

<form method='post' onsubmit='return submitData();' action="<?php echo \Pasteque\get_module_url_action(PLUGIN_NAME, 'composition_edit');?>">
        <input id="inputData" name="inputData" type="text" style="display:none">
        <?php \Pasteque\form_save();?>
</form>

<script src="<?php echo \Pasteque\get_module_action(PLUGIN_NAME, "model.js")?>" type="text/javascript"></script>
<script src="<?php echo \Pasteque\get_module_action(PLUGIN_NAME, "control.js")?>" type="text/javascript"></script>

<script type="text/javascript">
    currentCategory = null;
    var productsByCategory = new Array();
    var products = new Array();

	centerImage = function(selector) {
		var container = jQuery(selector);
		var img = container.children("img");
		var containerWidth = parseInt(container.css('width'));
		var containerHeight = parseInt(container.css('height'));
		var imgWidth = parseInt(img.css('width'));
		var imgHeight = parseInt(img.css('height'));
		var hOffset = (containerWidth - imgWidth) / 2;
		var vOffset = (containerHeight - imgHeight) / 2;
		img.css("left", hOffset + "px");
		img.css("top", vOffset + "px");
	}

	jQuery().ready(function() {
<?php foreach ($categories as $category) {
	echo "\t\tcenterImage('#category-" . $category->id . "');\n";
} ?>
	});

	addProductToCat = function(product, category) {
		if (typeof(productsByCategory[category]) != 'object') {
			productsByCategory[category] = new Array();
		}
		productsByCategory[category].push(product);
	}
<?php foreach ($products as $product) {
	echo "\taddProductToCat(\"" . $product->id . "\", \"" . $product->category->id . "\");\n";
	echo "\tproducts[\"" . $product->id . "\"] = {\"id\":\"" . $product->id . "\", \"label\": \"" . $product->label . "\", \"reference\": \"" . $product->reference . "\", \"img\": \"?" . \Pasteque\URL_ACTION_PARAM . "=img&w=product&id=" . $product->id . "\"};\n";
} ?>

    showProduct = function(productId) {
		var product = products[productId];
		html = "<a id=\"product-" + productId + "\"class=\"catalog-product\" onClick=\"javascript:addProduct('" + product['id'] + "');return false;\">";
		html += "<img src=\"" + product["img"] + "\" />";
		html += "<p>" + product['label'] + "</p>";
		html += "</a>";
		jQuery("#products").append(html);
		centerImage("#product-" + productId);
	}

    changeCategory = function(category) {
		jQuery("#products").html("");
		var prdCat = productsByCategory[category];
		for (var i = 0; i < prdCat.length; i++) {
			showProduct(prdCat[i]);
		}
    currentCategory = category;

    // modify label of button add all product:
    var btn= $("#btnAddAllPrd input").val();
    var res = btn.substr(0, btn.indexOf(':') + 1)
            + " " + $("#category-" + category + " p").html();
    $("#btnAddAllPrd input").val(res);
	}


<?php if (count($categories) > 0) {
	echo "\tchangeCategory(\"" . $categories[0]->id . "\");\n";
} ?>

	var tax_rates = new Array();
<?php foreach ($taxes as $tax) {
	echo "\ttax_rates['" . $tax->id . "'] = " . $tax->getCurrentTax()->rate . ",\n";
} ?>


updateSellPrice = function() {
		var sellvat = jQuery("#sellvat").val();
		var rate = tax_rates[jQuery("#edit-tax_cat").val()];
		var sell = sellvat / (1 + rate);
		jQuery("#realsell").val(sell);
		jQuery("#sell").val(sell.toFixed(2));
		updateMargin();
	}
updateSellVatPrice = function() {
		// Update sellvat price
		var sell = jQuery("#sell").val();
		var rate = tax_rates[jQuery("#edit-tax_cat").val()];
		var sellvat = sell * (1 + rate);
		// Round to 2 decimals and refresh sell price to avoid unrounded payments
		sellvat = sellvat.toFixed(2);
		jQuery("#sellvat").val(sellvat);
		updateSellPrice();
		updateMargin();
	}
updateMargin = function() {
		var sell = jQuery("#realsell").val();
		var buy = jQuery("#edit-price_buy").val();
		var ratio = sell / buy - 1;
		var margin = (ratio * 100).toFixed(2) + "%";
		var rate = (sell / buy).toFixed(2);
		jQuery("#margin").val(margin + "\t\t" + rate);
	}
	updateMargin();

    /**Replace ',' by '.' and call function 'fonction'
     * @param id the id of HTML input element
     * @param fonction the function after replace*/
    function changeVal(id, fonction) {
        var val = $("#" + id).val().replace(",", ".");
        jQuery(id).val(val);
        fonction();
    }

    jQuery("#sellvat").change(function() {changeVal(this.id, updateSellPrice)});

    jQuery("#edit-tax_cat").change(function() {changeVal(this.id, updateSellPrice)});

    jQuery("#sell").change(function() {changeVal(this.id, updateSellVatPrice);});

    jQuery("#edit-price_buy").change(function() {changeVal(this.id, updateMargin);});

    jQuery("#edit-discount_rate").change(function() {changeVal(this.id, updateMargin);});

    jQuery("#edit-sgNewName").change(function() {editSubGroup();});

    jQuery("#edit-sgOrder").change(function() { editSubGroup();});

    updateBarcode = function() {
		var barcode = jQuery("#barcode").val();
		var src = "?<?php echo \Pasteque\URL_ACTION_PARAM; ?>=img&w=barcode&code=" + barcode;
		jQuery("#barcodeImg").attr("src", src);
	}

	updateBarcode();
	jQuery("#barcode").change(updateBarcode);
    generateBarcode = function() {
		var first = Math.floor(Math.random() * 9) + 1;
		var code = new Array();
		code.push(first);
		for (var i = 0; i < 11; i++) {
			var num = Math.floor(Math.random() * 10);
			code.push(num);
		}
		var checksum = 0;
		for (var i = 0; i < code.length; i++) {
			var weight = 1;
			if (i % 2 == 1) {
				weight = 3;
			}
			checksum = checksum + weight * code[i];
		}
		checksum = checksum % 10;
		if (checksum != 0) {
			checksum = 10 - checksum;
		}
		code.push(checksum);
		var barcode = code.join("");
		jQuery("#barcode").val(barcode);
		updateBarcode();
	}

    /** Add all product contain in the category */
    addAllPrd = function() {
        var prdCat = productsByCategory[currentCategory];
            for (var i = 0; i < prdCat.length; i++) {
                addProduct(prdCat[i]);
            }
    }

</script>

<?php
    echo "<script type='text/javascript'>";
if (isset($compo)) {
    echo "addDataCmp(";
    echo "'" . $compo->id . "', '" . $compo->reference
            . "', '" . $compo->label . "', '" . $compo->disp_order
            . "', '" . $compo->visible . "', '" . $compo->price_sell 
            . "', '" . $compo->price_buy . "', null, '" . $compo->tax_cat->label
            . "', '" . $compo->barcode . "', '" . $compo->discount_enabled
            . "', '" . $compo->discount_rate . "', '" . $compo->image 
            . "');\n";
    if ($compo->groups !== NULL) {
        foreach ($compo->groups as $subG) {
            echo "addDataSg('"  . $subG->id . "', '" . $subG->label
                    . "', '" . $subG->image . "','"  . $subG->dispOrder
                    . "', 'status');\n";
            if ($subG->groups !== NULL) {
                foreach ($subG->groups as $prodG) {
                    echo "addDataSgPrd('" . $prodG->subgroup 
                            . "', '" . $prodG->product . "', '" . $prodG->label
                            . "', '" . $prodG->dispOrder . "' , 'status');\n";
                }
            }
        }
    }
}
    echo "showSubgroup();\n";
    echo "ERR_COMPOSITION_UNDEFINED = \"" . i18n("ERR_COMPOSITION_UNDEFINED", PLUGIN_NAME) . "\";\n";
    echo "ERR_COMPOSITION_NAME_EMPTY = \"" . i18n("ERR_COMPOSITION_NAME_EMPTY", PLUGIN_NAME) . "\";\n";
    echo "ERR_COMPOSITION_NAME = \"" . i18n("ERR_COMPOSITION_NAME", PLUGIN_NAME) . "\";\n";
    echo "ERR_SUBGROUP_NAME_EMPTY = \"" . i18n("ERR_SUBGROUP_NAME_EMPTY", PLUGIN_NAME) . "\";\n";
    echo "ERR_SUBGROUP_NAME = \"" . i18n("ERR_SUBGROUP_NAME", PLUGIN_NAME) . "\";\n";
    echo "ERR_SUBGROUP_UNDEFINED = \"" . i18n("ERR_SUBGROUP_UNDEFINED", PLUGIN_NAME) . "\";\n";
    echo "ERR_PRD_EXIST = \"" . i18n("ERR_PRD_EXIST", PLUGIN_NAME) . "\";\n";
    echo "ERR_COMPOSITION_REF_EMPTY = \"" . i18n("ERR_COMPOSITION_REF_EMPTY", PLUGIN_NAME). "\";\n";
    echo "ERR_COMPOSITION_REF = \"" . i18n("ERR_COMPOSITION_REF", PLUGIN_NAME) . "\";\n";
    echo "</script>";

?>