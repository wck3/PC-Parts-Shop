<?php
require(__DIR__ . "/../../partials/nav.php");
require_once(__DIR__ . "/../../lib/functions.php");

if(!is_logged_in()){
    //redirected to login page if not logged in
    flash("You must be logged in to view this page", "warning");
    redirect("$BASE_PATH/login.php");
}

//Fetch cart information to display items in checkout
$checkout_valid=true;
$user_id = get_user_id();
$results=[];
$price_check=[];
$stock_err=[];

if ($user_id > 0) {
    $db = getDB();
    //for verifying price between cart and items tabls
    $stmt = $db->prepare("SELECT c.price, i.price FROM Shop_Cart c, Shop_Items i WHERE c.item_id = i.id and c.price <> i.price and c.user_id = :uid");
    //update price in cart if needed (logic below)
    $stmt2 = $db->prepare("UPDATE Shop_Cart c, Shop_Items i SET c.price = i.price WHERE c.item_id = i.id AND c.user_id = :uid");
    //queries all cart items that exceed quantity of store items
    $stmt3 = $db->prepare("SELECT name, i.stock FROM Shop_Items i, Shop_Cart c WHERE i.id = c.item_id AND (c.quantity > i.stock) AND c.user_id = :uid");
    //retrieve cart info
    $stmt4 = $db->prepare("SELECT name, c.id as line_id, item_id, quantity,description ,CAST(c.price / 100.00 AS decimal(18,2)) AS price , CAST((c.price*quantity) / 100.00 AS decimal(18,2)) as subtotal FROM Shop_Items i JOIN Shop_Cart c on c.item_id = i.id WHERE c.user_id = :uid");
    try {
        
        $stmt->execute([":uid" => $user_id]);
        $price_check = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //if there is an item in carts with wrong price, user will be warned and the price will be updated in the cart
        if($price_check){
            flash("the price of an item has been changed since being added to cart", "warning");
            $stmt2->execute([":uid" => $user_id]);
        }
        
        //WCK3 04/27/2022
        //if the cart items exceed/fall short of the number of available items, the user is warned
        $stmt3->execute([":uid" => $user_id]);
        $stock_err = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        if($stock_err){
            foreach ($stock_err as $item){
                $checkout_valid=false;
                flash("Cart contains more/less of item " . $item["name"] .  " than available stock: " . $item["stock"], "warning");
            }
        }
        
        //cart items are retrieved (after any price updates)
        $stmt4->execute([":uid" => $user_id]);
        $results = $stmt4->fetchAll(PDO::FETCH_ASSOC);
        if ($results) {
            $cart_items = $results;
        }

    } catch (PDOException $e) {
        echo $e;
        error_log("Error fetching cart" . var_export($e, true));
    }
}

if(empty($results)){
    flash("You must have items to your cart to checkout", "warning");
    redirect("$BASE_PATH/shop.php");
}

?>

<div class="container-fluid">
    <br>
    <div class="card bg-dark">
        <div class="card-header">
            <div class="h3">Checkout</div>
        </div>
        <div class="card-body">
            <div class="row">

                <div class="col-4">
                    <div class="h4">Payment Info</div>
                    <form method="POST" onsubmit="return validate(this)">
                        <div class="mb-3">
                            <label for="username">Username</label>
                            <input  class="form-control" type="text" name="username" value="<?php se(get_username()); ?>"/>
                        </div>
                        <div class="mb-3">
                            <label for="address">Address</label>
                            <input  class="form-control" type="text" name="address"/>
                        </div>
                        <div class="mb-3">
                           
                            <label for="address">City</label>
                            <input class="form-control" type="text" name="City"/>
                                
                        </div>
                        <div class="mb-3">
                               <!-- partial used for state dropdown -->
                               <?php require(__DIR__ . "/../../partials/address_fields.php");?>
                        </div>
                        <div class="mb-3">
                           
                            <label for="address">Zip/postal code</label>
                            <input  class="form-control-2 rounded-3" type="text" name="zip"/>
                              
                        </div>
                        <div class="mb-3">
                            <label for="payment">Payment Method: </label>
                            <!--<input class="form-control" type="text" name="payment"  /> -->
                            <select class="form-control-md-3 bg-white rounded-3" name="payment_method">
                    
                                <option value="" selected>Select</i></option> 
                                <option value="Cash" name="Cash" >Cash</option>
                                <option value="Visa" name="Visa">Visa</option>
                                <option value="MasterCard" name="MasterCard">MasterCard</option>
                                <option value="Amex" name="Amex" >Amex</option>
                                <option value="PayPal" name="PayPal" >PayPal</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payment_total">Payment Total: $</label>
                            <input class="form-control-3 rounded-3" type="number" min="0" step="0.01" name="payment"  />
                        </div>
                           <input class="btn btn-secondary" type="submit" value="Confirm Purchase" /> 
                    </form>
                </div>
                <div class="col-2"></div>
                <div class="col-5">
                    <div class="h4">Items</div>
                    <table class="table text-white">
                        <thead>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </thead>
                        <tbody>
                            <?php $total=0; foreach ($cart_items as $item) : $total+=$item["subtotal"];?>
                                <tr>
                                    <td><?php se($item, "name"); ?></td>
                                    <td><?php se($item, "description"); ?></td>
                                    <td> <?php se($item, "quantity"); ?></td>
                                    <td>$<?php se($item, "subtotal"); ?></td>
                                </tr>
                            <?php endforeach; $total;?>
                        </tbody>
                    </table>
                    <div class="row">
                        <div class="col-md-3">
                            Total: $<?php se($total); ?>
                        </div>
                        <div class="col-md-3">

                        <div class="col">
                            <form action="cart.php" method="GET">
                                <button type="submit" class="btn btn-sm btn-secondary">Return to Cart</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- end card body -->
    </div> <!--  end card --> 
   
   
</div> <!-- End Page-->

<script>
    //WCK3 4/26/2022 client side payment method validation
    function validate(form) {
     
        let username=form.username.value;
        let address=form.address.value;
        let city=form.City.value;
        let state=form.state.value;
        let payment_method=form.payment_method.value;
        let money_recieved=form.payment.value;
        let zipcode=form.zip.value;
        let isValid=true;

         
        //regex expressions to validate various payment method fields
        var usernamePattern = /[a-zA-Z0-9_-]{3,16}$/;
        var addressPattern = /^[#.0-9a-zA-Z\s,-]+$/;
        var cityPattern = /^([a-zA-Z\u0080-\u024F]+(?:. |-| |'))*[a-zA-Z\u0080-\u024F]*$/;
        var currencyPattern = /^(?=.*?\d)^\$?(([1-9]\d{0,2}(,\d{3})*)|\d+)?(\.\d{1,2})?$/;
        var current_user = "<?php se(get_username()); ?>";

        var cart_price = "<?php se($total); ?>";
       

        if((username.length < 3 || username.length > 16) || !usernamePattern.test(username)){
            isValid=false;
            flash("Invalid username. Username must only contain 3-16 characters a-z, 0-9, _, or -", "danger");
        }
    
        if(username != current_user){
            isValid=false;
            flash("Username does not match account username", "warning");
        }
        if(!addressPattern.test(address) || address.length==0){
            isValid=false;
            flash("Please enter a valid address", "warning");
        }
        if(!cityPattern.test(city) || city.length==0){
            isValid=false;
            flash("Please enter a valid city", "warning");
        }
        
        //validates using array defined in address_fields partial
        if(!states.includes(state)){
            isValid=false;
            flash("Please enter a valid state", "warning");
        }
        payment_methods=["Cash", "Visa", "MasterCard", "Amex", "PayPal"];
        if(!payment_methods.includes(payment_method)){
            isValid=false;
            flash("Please enter a valid payment method", "warning");
        }

        if(!currencyPattern.test(money_recieved) || money_recieved.length==0){
            isValid=false;
            flash("Please enter a valid payment amount in USD", "warning");
        }
        if(money_recieved != cart_price){
            isValid=false;
            flash("Entered payment total does not match cart price", "warning");
        }
        if(zipcode.length < 5 || zipcode.length > 5){
            isValid=false;
            flash("Please enter a vaild 5-digit zipcode", "warning");
        }
        
        if(isValid){
            console.log("client side validation successful");
        }
        return isValid;
    }
</script>

<?php 


//if all fields set and validated checkout can occur
if(isset($_POST["address"]) && isset($_POST["City"]) && isset($_POST["state"]) && isset($_POST["zip"]) && isset($_POST["payment_method"]) && isset($_POST["payment"]) ){
    
    $address = se($_POST, "address", "", false) . " " . se($_POST, "City", "", false) . ", " 
    . se($_POST, "state", "", false) . " " . se($_POST, "zip", "", false) ;

    $pay_method = se($_POST, "payment_method", "", false);
    $money_recieved = se($_POST, "payment", "", false)*100;
    
    //only checkout if items are in stock
    if($checkout_valid){
        //add to order table
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO Shop_Orders (user_id, total_price, address, payment_method, money_recieved) VALUES(:id, :price, :address, :p_method, :money)");
        try {
            $stmt->execute([":id" => $user_id, ":price" => $total*100, ":address" => $address, ":p_method" => $pay_method, ":money" => $money_recieved]);
            //flash("add to order", "success");
        } catch (PDOException $e) {
            echo $e;
            error_log("Error placing order" . var_export($e, true));
        }
        
        $order_ID=[];
        //get last order ID for user
        $db = getDB();
        $stmt = $db->prepare("SELECT id FROM Shop_Orders WHERE user_id = :id ORDER BY id DESC LIMIT 1  ");
        try {
            $stmt->execute([":id" => $user_id]);
            $order_ID = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo $e;
            error_log("Error fetching Order ID" . var_export($e, true));
        }


        foreach ($order_ID as $item){
            $curr_ID = $item["id"];
        }
        //insert items into order table with given ID
        foreach ($cart_items as $item){
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO Order_Items (order_id, item_id, quantity,  unit_price) VALUES(:order_id, :item_id, :quantity, :price)");
            try {
                $stmt->execute([":order_id" => $curr_ID, ":item_id" => $item["item_id"], ":quantity" => $item["quantity"], ":price" => $item["price"]*100]);
                //flash("add to order items", "success");
            } catch (PDOException $e) {
                echo $e;
                error_log("Error adding to order items" . var_export($e, true));
            }
            
        }
        //update product table with quantity change
        $stmt = $db->prepare("UPDATE Shop_Items set stock = stock - (select IFNULL(quantity, 0) FROM Shop_Cart WHERE item_id = Shop_Items.id and user_id = :uid) 
            WHERE id in (SELECT item_id from Shop_Cart where user_id = :uid)");
        try {
            $stmt->execute([":uid" => $user_id]);
        } catch (PDOException $e) {
            error_log("Update stock error: " . var_export($e, true));
                   
        }

        //clear user cart after purchase
        $stmt = $db->prepare("DELETE FROM Shop_Cart WHERE user_id = :uid");
        try{
            $stmt->execute([":uid" => $user_id]);
        } catch (PDOException $e) {
            error_log("Update stock error: " . var_export($e, true));
        }
        //redirect to thank you page once order processed
        echo "<script>window.location.href='thankyou.php'</script>";
    
    } 
    
}

?>

<?php 
require_once(__DIR__ . "/../../partials/flash.php");
?>