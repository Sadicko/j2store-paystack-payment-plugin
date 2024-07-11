<?php
defined('_JEXEC') or die('Restricted access');
?>
<form id="paystack-form" action="<?php echo $vars->callback_url; ?>" method="post">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <input type="hidden" name="order_id" value="<?php echo $vars->order_id; ?>" />
    <input type="hidden" name="amount" value="<?php echo $vars->amount * 100; ?>" />
    <input type="hidden" name="email" value="<?php echo $vars->user_email; ?>" />
    <input type="hidden" name="currency" value="GHS" />
    <button type="button" onclick="payWithPaystack()">Pay Now</button>
</form>

<script type="text/javascript">
function payWithPaystack() {
    var handler = PaystackPop.setup({
        key: '<?php echo $vars->public_key; ?>',
        email: '<?php echo $vars->user_email; ?>',
        amount: '<?php echo $vars->amount * 100; ?>',
        currency: 'GHS',
        ref: '' + Math.floor((Math.random() * 1000000000) + 1),
        callback: function(response) {
            document.getElementById('paystack-form').submit();
        },
        onClose: function() {
            alert('Payment window closed.');
        }
    });
    handler.openIframe();
}
</script>
