<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Express Checkout</title>
    <script src="https://www.paypal.com/sdk/js?client-id=AR2TADjytHS8cSWTgG2_N8oCPftDQliFz2o3cpoT-z3DhlC_JMjQQgFUw4bluT1VMJIDP-pgv3qtdPm7&currency=MXN"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('payment-form');
            var amountInput = document.getElementById('amount');

            paypal.Buttons({
                createOrder: function(data, actions) {
                    var amount = amountInput.value;
                    if (!amount) {
                        alert('Please enter an amount.');
                        return;
                    }

                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: amount
                            }
                        }]
                    }).then(function(orderID) {
                        // Save order to the database
                        return fetch('/save-order.php', {
                            method: 'post',
                            headers: {
                                'content-type': 'application/json'
                            },
                            body: JSON.stringify({
                                orderID: orderID,
                                amount: amount
                            })
                        }).then(function(response) {
                            console.log("createOrder: ")
                            console.log(response)
                            if (response.ok) {
                                return orderID;
                            } else {
                                alert('Failed to save order.');
                                throw new Error('Failed to save order.');
                            }
                        });
                    });
                },
                onApprove: function(data, actions) {
                    return actions.order.capture().then(function(details) {
                        return fetch('/executePayment.php', {
                            method: 'post',
                            headers: {
                                'content-type': 'application/json'
                            },
                            body: JSON.stringify({
                                orderID: data.orderID
                            })
                        }).then(function(response) {
                            console.log("onApprove: ")
                            console.log(response)
                            if (response.ok) {
                                alert('Transaction completed by ' + details.payer.name.given_name);
                            } else {
                                alert('Transaction failed.');
                            }
                        });
                    });
                }
            }).render('#paypal-button-container');
        });
    </script>
</head>
<body>
<form id="payment-form">
    <label for="amount">Amount to Pay:</label>
    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
    <div id="paypal-button-container"></div>
</form>
</body>
</html>