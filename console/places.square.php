<?php

use \WideImage\WideImage;

security_check();
admin_check();

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{

    // Basic serverside validation
    if (
        !validate_blank($_POST['building_id']))
    {
        message_set('Square Error', 'There was an error with your square information.', 'red');
        header_redirect('/maps/square/'.$_GET['key']);
    }

    $query = 'UPDATE squares SET
        building_rules = "'.addslashes($_POST['building_rules']).'"
        WHERE id = '.$_GET['key'].'
        LIMIT 1';
    mysqli_query($connect, $query);

    foreach($_FILES as $key => $value)
    {
        if(in_array($value['type'], FILE_TYPES_IMAGES))
        {
            $query = 'DELETE FROM square_images
                WHERE square_id = "'.$_GET['key'].'"
                AND direction = "'.$key.'"';
            mysqli_query($connect, $query);

            $image = Wideimage::load($_FILES[$key]['tmp_name']);
            $image = $image->resize(1920, 1080, 'outside');
            $image = $image->crop('center', 'center', 1920, 1080);
            $image = 'data:image/jpeg;base64, '.base64_encode($image->asString('jpg'));
        
            $query = 'INSERT INTO square_images (
                    square_id,
                    image,
                    direction,
                    created_at,
                    updated_at
                ) VALUES (
                    "'.$_GET['key'].'",
                    "'.addslashes($image).'",
                    "'.$key.'",
                    NOW(),
                    NOW()
                )';
            mysqli_query($connect, $query);
        }
    }   

    $query = 'DELETE FROM building_square
        WHERE square_id = "'.$_GET['key'].'"';
    mysqli_query($connect, $query);

    foreach($_POST['building_id'] as $value)
    {
        $query = 'INSERT INTO building_square (
                building_id,
                square_id
            ) VALUES (
                "'.$value.'",
                "'.$_GET['key'].'"
            )';
        mysqli_query($connect, $query);
    }

    message_set('Square Success', 'Square has been updated.');
    // header_redirect('/places/square/'.$_GET['key']);
    header_redirect('/places/dashboard');
    
}
elseif(isset($_GET['delete']))
{

    $query = 'DELETE FROM square_images 
        WHERE square_id = "'.$_GET['key'].'"
        AND direction = "'.$_GET['delete'].'"
        LIMIT 1';
    mysqli_query($connect, $query);
    
    message_set('Square Image Success', 'Square image has been updated.');
    header_redirect('/places/square/'.$_GET['key']);

}

define('APP_NAME', 'Maps');

define('PAGE_TITLE', 'Modify Map Squares');
define('PAGE_SELECTED_SECTION', 'geography');
define('PAGE_SELECTED_SUB_PAGE', '/maps/squares');

include('../templates/html_header.php');
include('../templates/nav_header.php');
include('../templates/nav_slideout.php');
include('../templates/nav_sidebar.php');
include('../templates/main_header.php');

include('../templates/message.php');

$square = square_fetch($_GET['key']);

?>

<!-- CONTENT -->

<h1 class="w3-margin-top w3-margin-bottom">
    <img
        src="https://cdn.brickmmo.com/icons@1.0.0/places.png"
        height="50"
        style="vertical-align: top"
    />
    Places
</h1>
<p>
    <a href="/city/dashboard">Dashboard</a> / 
    <a href="/places/dashboard">Places</a> / 
    Modify Building Square
</p>
<hr />
<h2>Modify Building Square</h2>

<form
    method="post"
    novalidate
    id="main-form"
    enctype="multipart/form-data"
>

    <?=form_select_table('building_id', 'buildings', 'id', 'name', array('multiple' => true, 'selected' => $square['buildings'], 'first' => true))?>
    <label for="building_id" class="w3-text-gray">
        Building <span id="building-id-error" class="w3-text-red"></span>
    </label>

    <input  
        name="building_rules" 
        class="w3-input w3-border w3-margin-top" 
        type="text" 
        id="building_rules" 
        value="<?=$square['building_rules']?>"
    />
    <label for="building_rules" class="w3-text-gray">
        Building Rules <span id="building-rules-error" class="w3-text-red"></span>
    </label>

    <?php foreach(DIRECTIONS as $direction): ?>

        <?php if(isset($square[$direction])): ?>
            <div class="w3-margin-top">
                <img src="<?=$square[$direction]?>" style="max-width:300px" />
            </div>
            <div class="w3-margin-top">
                <a href="#" onclick="return confirmModal('Are you sure you want to delete this image?', '/places/square/delete/<?=$direction?>/<?=$_GET['key']?>');">
                    <i class="fa-solid fa-trash-can"></i> Delete Image
                </a>
            </div>
        <?php endif; ?>

        <input  
            name="<?=$direction?>"
            class="w3-input w3-border w3-margin-top" 
            type="file" 
            id="<?=$direction?>" 
            autocomplete="off"
        />
        <label for="<?=$direction?>" class="w3-text-gray">
            <?=ucfirst($direction)?> Photo
        </label>

    <?php endforeach; ?>

    

    <button class="w3-block w3-btn w3-orange w3-text-white w3-margin-top" onclick="return validateMainForm();">
        <i class="fa-solid fa-pen fa-padding-right"></i>
        Update Square
    </button>
</form>

<script>

    function validateMainForm() {
        let errors = 0;

        let type = document.getElementById("type");
        let type_error = document.getElementById("type-error");
        type_error.innerHTML = "";
        if (type.value == "") {
            type_error.innerHTML = "(type is required)";
            errors++;
        }

        if (errors) return false;
    }

</script>

    
<?php

include('../templates/modal_city.php');

include('../templates/main_footer.php');
include('../templates/debug.php');
include('../templates/html_footer.php');
