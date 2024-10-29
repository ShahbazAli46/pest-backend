<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quote->is_contracted ? 'Contract Details' : 'Quote Details' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 20px;
            background-color: #f4f4f4;
            color: #333;
            line-height: 1.6;
        }
        h1 {
            text-align: center;
            color: #2c3e50;
        }
        .invoice-details {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .invoice-details p {
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            padding: 10px 15px;
            margin: 10px 0;
            color: #fff;
            background-color: #3498db;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .button:hover {
            background-color: #2980b9;
        }
        .total {
            font-weight: bold;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <h1>{{ $quote->is_contracted ? 'Contract Details' : 'Quote Details' }}</h1>
    <div class="invoice-details">
        <p>Hello, here are the details of your {{ $quote->is_contracted ? 'contract' : 'quote' }}:</p>

        <p><strong>Title:</strong> {{ $quote->quote_title }}</p>
        <p><strong>Subtotal:</strong> {{ $quote->sub_total }}</p>
        <p><strong>Discount:</strong> {{ $quote->dis_per }}% ({{ $quote->dis_amt }})</p>
        <p><strong>VAT:</strong> {{ $quote->vat_per }}% ({{ $quote->vat_amt }})</p>
        <p><strong>Grand Total:</strong> {{ $quote->grand_total }}</p>
    </div>

    <a href="http://pestcontrol.worldcitizenconsultants.com/quotePdf/?id={{ $quote->id }}" title="for detail" class="button" style="color: white">
        Click Here for Details
    </a>
</body>
</html>
