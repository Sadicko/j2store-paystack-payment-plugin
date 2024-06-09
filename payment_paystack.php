<?php
defined('_JEXEC') or die('Restricted access');

class plgJ2StorePayment_Paystack extends JPlugin {

    public function __construct(& $subject, $config) {
        parent::__construct($subject, $config);
        $this->loadLanguage();
    }

    public function onJ2StoreGetPaymentOptions($element, $order) {
        if ($element->element == 'payment_paystack') {
            $html = '<input type="radio" name="payment_method" value="payment_paystack" id="payment_paystack" />';
            $html .= '<label for="payment_paystack">Paystack</label>';
            return $html;
        }
    }

    public function onJ2StoreProcessPayment($order) {
        $app = JFactory::getApplication();
        $public_key = $this->params->get('public_key');
        $secret_key = $this->params->get('secret_key');
        $live_mode = $this->params->get('live_mode');
        $currency = $this->params->get('currency', 'GHS'); // Default to GHS if not set
        $order_id = $order->get('order_id');
        $amount = $order->get('order_total') * 100; // Convert to smallest currency unit
        $callback_url = JURI::root() . 'index.php?option=com_j2store&view=checkout&task=confirmPayment&order_id=' . $order_id;

        $html = '<script src="https://js.paystack.co/v1/inline.js"></script>';
        $html .= '<button type="button" onclick="payWithPaystack()">Pay Now</button>';
        $html .= '<script>
            function payWithPaystack() {
                var handler = PaystackPop.setup({
                    key: "' . $public_key . '",
                    email: "' . $order->get('user_email') . '",
                    amount: ' . $amount . ',
                    currency: "' . $currency . '",
                    ref: "' . $order_id . '",
                    callback: function(response) {
                        window.location.href = "' . $callback_url . '&reference=" + response.reference;
                    },
                    onClose: function() {
                        alert("Payment window closed");
                    }
                });
                handler.openIframe();
            }
        </script>';
        return $html;
    }

    public function onJ2StoreConfirmPayment($order) {
        $app = JFactory::getApplication();
        $reference = $app->input->get('reference', '', 'string');
        $secret_key = $this->params->get('secret_key');
        $order_id = $order->get('order_id');

        // Verify the transaction
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.paystack.co/transaction/verify/' . $reference);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secret_key]);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response);

        if ($result->status && $result->data->status == 'success') {
            $order->payment_complete();
            $app->enqueueMessage('Payment successful', 'message');
        } else {
            $order->payment_failed();
            $app->enqueueMessage('Payment failed', 'error');
        }
    }
}
