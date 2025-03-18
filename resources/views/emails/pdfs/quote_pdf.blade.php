<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        /* DomPDF specific adjustments */
        @page {
            margin: 0cm;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            padding-top: 3.5cm;
            padding-bottom: 3.5cm;
        }
     
        .header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 3cm;
            text-align: center;
            padding-top: 10px;
        }
        
        .footer {
            position: fixed;
            bottom: 2cm;
            left: 0cm;
            right: 0cm;
            height: 2.2cm;
            text-align: center;
        }

        .side-image {
            position: fixed;
            left: 0px;
            top: 200px;
        }
        
        .content {
            padding:0 1cm 0.3cm 1.5cm;
            margin-left: 0cm;
        }
        
        .page-break {
            page-break-after: always;
        }

        .total-section {
            page-break-inside: avoid;
        }

        h4{
            margin-top: 5px;
            margin-bottom: 5px;
        }
        
        p {
            line-height: 1.5;
            margin-top: 2px;
            margin-bottom: 2px;
            font-size: 12px
        }
        .left-div{
            float: left;
        }

        /* table */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 12px;
            border: 2px solid #dddddd; /* Outer border */
        }

        .custom-table th,
        .custom-table td {
            padding: 8px;
            text-align: left;
        }

        .custom-table th.text-right,
        .custom-table td.text-right {
            text-align: right;
        }

        .header-row {
            background-color: #e6f9e6; /* Light green */
            font-weight: bold;
        }

        .grand-total {
            background-color: #28a745; /* Green */
            font-weight: bold;
            color: white;
        }

        .custom-table tr td {
            border-bottom: 1px solid #ccc; /* Default horizontal border */
        }

        /* Ensure the first and last rows have no bottom border */
        .custom-table .header-row td,
        .custom-table .grand-total td {
            border-bottom: none;
        }


        /* service table */
        .service-table {
            width: 100%;
            border-collapse: collapse;
            font-size:12px;
        }

        .service-header {
            background-color: #28a745; /* Green background */
            color: white;
            text-align: center;
            padding: 10px;
        }

        .service-index, .service-units, .service-rate, .service-frequency, .service-total {
            text-align: center;
        }

        .service-desc {
            text-align: left;
            padding: 8px;
        }

        .service-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .bold-text {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('assets/pdf/logo.svg') }}" width="200" />
    </div>
    
    <div class="footer">
        <img src="{{ public_path('assets/pdf/compl_footer.svg') }}" width="100%" />
    </div>
    
    <div class="side-image">
        <img src="{{ public_path('assets/pdf/right_asset.svg') }}" width="45" />
    </div>
    
    <div class="content">
        <div class="row-container">
            <div class="left-div" style="width:60%">
                <h4>Accurate Pest Control Services LLC</h4>    
                <p>{{$data['quote']->branch->address??''}}</p> 
                {{-- <p>Industrial Area 4, Dubai</p>  --}}
                <p>info@accuratepestcontrol.ae</p>
                <p>0521528725</p>
                <p>TRN: 104136880200003</p>
                
                <h4>Mr {{$data['quote']->user->name}}</h4>    
                <p>{{$data['quote']->clientAddress->address}} {{$data['quote']->clientAddress->state}}, {{$data['quote']->clientAddress->country}}</p>
                {{-- <p>PO Box No: 00000</p> --}}
                <p>{{$data['quote']->user->email}}</p>
                {{-- <p>TRN: 100587820000003</p> --}}
            </div>
            <div class="left-div" style="width:38%">
                <h4 style="text-align: right">Quotation</h4>
                <table class="custom-table">
                    <tr class="header-row">
                        <th>Ref#</th>
                        <th class="text-right">{{$data['quote']->id}}/{{ now()->format('y') }}</th>
                    </tr>
                    <tr>
                        <td>Date</td>
                        <td class="text-right">{{ $data['quote']->updated_at->format('d-M-Y') }}</td>
                    </tr>
                
                    <tr>
                        <td>Valid Until</td>
                        <td class="text-right">{{ $data['quote']->updated_at->addDays(15)->format('d-M-Y') }}</td>
                    </tr>
                
                    <tr class="grand-total">
                        <td>Grand Total</td>
                        <td class="text-right">AED {{ number_format($data['quote']->grand_total, 2) }}</td>
                    </tr>
                </table>                        
            </div>
            <div style="clear: both"></div>
        </div>

        <div class="">
            <br>
            <h4><i>Contact Person</i></h4>
            <p>Mr {{$data['quote']->user->name}}</p>
            {{-- <p>QC Incharge</p> --}}
            <p>{{$data['quote']->user->email}}</p>
            <p>{{$data['quote']->client->phone_number}}</p>
            <br>
            <p><b>Subject: </b>{{$data['quote']->subject}}</p>
            <br>
            <p><b>Dear Sir,</b></p>
            <p>After the survey and inspection, we are pleased to submit our lowest offer for Integrated General Pest Control Service for your kind perusal.</p>
        </div>

        <div>
            <h3>1. &nbsp;SCOPE OF SERVICES</h3>
            <P>APCS will furnish all supervision, labor, material, equipment necessary to accomplish the monitoring, trapping, chemical control methods and
                pest removal components of IPM program. In addition, we will provide site speci</P>

            <ol type="a">
                @foreach ($data['quote']->uniqueQuoteServices as $quoteService)
                <li>
                    <b>{{$quoteService->service->service_title}}</b><br>
                    {!! $quoteService->service->term_and_conditions !!}
                </li>
                @endforeach
            </ol>
        </div>
        <div class="total-section">
            <table class="service-table">
                <thead>
                    <tr>
                        <th class="service-header">#</th>
                        <th class="service-header">Service Description</th>
                        <th class="service-header">Units</th>
                        <th class="service-header">Rate (AED)</th>
                        <th class="service-header">Frequency</th>
                        <th class="service-header">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['quote']->quoteServices as $quoteService) 
                    <tr>
                        <td class="service-index">{{$loop->iteration}}</td>
                        <td class="service-desc">
                            <strong>{{$quoteService->service->service_title}}</strong>
                            {{-- <br>
                            - General Pest (Cockroaches, Flies, and Rodent) Control Service<br>
                            - Al Aweer Factory - Monthly Two Times Service<br>
                            - Ras Al Khor Warehouse - Monthly Two Times Service<br>
                            - 09 Vehicles Monthly One-Time Service --}}
                        </td>
                        <td class="service-units">{{$quoteService->no_of_services}}</td>
                        <td class="service-rate">{{$quoteService->rate}}</td>
                        <td class="service-frequency" style=" text-transform: capitalize;">{{$quoteService->job_type}}</td>
                        <td class="service-total">{{ number_format($quoteService->sub_total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div style="float:right; width:58%" class="total-section">
            <br>
            <table class="custom-table"> 
                {{-- <tr class="header-row">
                    <th>Ref#</th>
                    <th class="text-right">2/23</th>
                </tr> --}}
                <tr>
                    <td class="bold-text">Subtotal</td>
                    <td class="text-right bold-text">{{ number_format($data['quote']->sub_total, 2) }}</td>
                </tr>
            
                <tr>
                    <td class="bold-text">VAT {{$data['quote']->vat_per}}%</td>
                    <td class="text-right bold-text">{{ number_format($data['quote']->vat_amt, 2) }}</td>
                </tr>
            
                <tr class="grand-total">
                    <td class="bold-text">Grand Total</td>
                    <td class="text-right bold-text">AED {{ number_format($data['quote']->grand_total, 2) }}</td>
                </tr>
            </table>                        
        </div>
        <div style="clear:both"></div>

        <div>
            <h5><i>Total Amount AED {{ number_format($data['quote']->grand_total, 2) }} ({{$data['quote']->amount_in_words}} only)</i></h5>
            <ul>
                <li>50% of the total amount to be invoiced upon signing the contract and payable within 30 days of the invoice date.</li>
                <li>Remaining balance to be invoiced after Six months from the starting date of the contract and payable within 30 days from the invoice
                    date.</li>
                <li>The Second Party is responsible to cover any additional fees such as governmental fees, attestation fees and payment related to
                    application of permit to work.</li>
            </ul>
        </div>

        <b><i>Best Regards</i></b>
        <h4>Mr. Muhammad Umair Khan</h4>
        <p>Operation Manager</p>
        <p>operations@accuratepestcontrol.ae</p>
        <p>0521283108</p>
    </div>


    <script type="text/php">
        if (isset($pdf)) {
            $text = "{PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("Arial", "bold");
            $size = 10;
            $color = array(0, 0, 0); // Black color
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = (($pdf->get_width() - $width) / 2)+70;
            $y = $pdf->get_height() - 113;
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>
</html>