<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Receipt Voucher</title>

    <style>
        * {
            margin: 1mm;
            /* padding: 5px 20px; */
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body{
            border: 1px solid gray;
            padding: 10px 20px;
            font-family: 'DejaVuSans', sans-serif;
        }
        .clear-fix{
            clear: both;
        }
        .content{
          padding: 0 15px;
        }
        .checkbox {
            width: 12px;
            height: 12px;
            display: inline-block;
            border: 1px solid black;
            border-radius: 5px;
            /* text-align: center; */
            font-size: 12px;
            margin-bottom: -3px;
            margin-right: 0px;
        }
        .checked {
            /* background-color: black; */
            color: white;
            content: "✔";
        }
        /* header */
        .logo{
          float:left;
          width: 33%;

        }
        .main-heading{
          float:left;
          width: 33%;

        }
        .address-date{
          float:left;
          width: 30%;
          padding: 10px;
          /* margin: 0px; */
        }
        .logo img{
          width: 275px;
        }
        .main-heading{
          text-decoration: underline;
          /* vertical-align: bottom; */
          position: relative;
          height: 165px;
        }
        .main-heading h1{
          position: absolute;
          bottom: 0;
        }
        .icon img{
          width:15px;
          margin-bottom: -2px;
          margin: 0px;
          padding: 0px;
        }
        .address-date table{
          width: 100%;
          border-collapse: collapse;
          border: 1px solid #000;
        }
        .address-date table th{
          background: black;
          color: white;
          
        }
        .address-date table th, td{
          border: 1px solid #000;
          text-align: center;
        }


        .input-group{
          border: 1px solid black;
          padding: 0px;
          margin-top: 15px;
          margin-bottom: 15px;
        }
        .dark-lable{
          background: black;
          color: white;
          margin-left: 0px;
          margin-right: 0px;
          padding:15px 8px;
          margin-bottom: -1px;
          margin-top: 0px;
        }
        .light-lable{
          margin-left: 0px;
          margin-right: 0px;
          padding:15px 8px;
          margin-bottom: -1px;
          margin-top: 0px;
          
        }

        .text-center{
          text-align: center;
        }
        .text-end{
          text-align: right;
        }
        .w-18{
          width: 18%;
        }
        .footer{
          margin-top: 40px;
        }
        .qr_code{
          width: 157px;
        }
        .fix-footer{
          background: #33a92f;
          padding-top: 10px;
          padding-bottom: 10px;
          position: fixed;
          bottom: 0px;
          left: 0px;
          right: 0px;
          width: 99.3%;
          color: white
        }
        .fix-footer h4{
          text-align: center;
        }
        .main-heading h1{
          font-size: 38px;
        }
    </style>
</head>
<body>
    <div class="header">
      <div class="logo">
        <img src="{{ public_path('assets/pdf/receipt_voucher/logo.svg') }}" alt="">
      </div>
      <div class="main-heading">
        <h1>Receipt Voucher</h1>
      </div>
      <div class="address-date">
        
        <span>
          <span class="icon"><img src="{{ public_path('assets/pdf/service_report/phone.svg') }}" alt=""></span> +971 52 152 8725
        </span>
        <span>
          <span class="icon"><img src="{{ public_path('assets/pdf/service_report/landline.svg') }}" alt=""></span>  06 5422661
        </span>
        <br>
        <span>
          <span class="icon"><img src="{{ public_path('assets/pdf/service_report/website.svg') }}" alt=""></span> www.accuratepestcontrol.ae
        </span>
        <br>
        <span>
          <span class="icon"><img src="{{ public_path('assets/pdf/service_report/email.svg') }}" alt=""></span> info@accuratepestcontrol.ae
        </span>
        <br>
        <span>
          <span class="icon"><img src="{{ public_path('assets/pdf/service_report/location.svg') }}" alt=""></span> Building No. 3702, Shop No. 3 & 4, Sharjah - UAE
        </span>

        <table>
          <tr>
            <th>Receipt No.</th>
            <th>Date</th>
          </tr>
          <tr>
            <td>4390</td>
            <td>20,Dec 2025</td>
          </tr>
        </table>
      </div>
    </div>
    <div class="clear-fix"></div>

        {{-- <span class="dark-lable w-18" >استلمنامن السيد/السيدة</span> --}}

    <div class="content">

      
      <div class="input-group"> 
        <span class="dark-lable w-18" style="float: left">Received From Mr. Ms.</span>
        <span class="input"></span>
        <span class="dark-lable w-18 text-end" style="float: right">Mr Hamza</span>
        <div class="clear-fix" style=" margin:0px"></div>
      </div>

      <div class="input-group"> 
        <span class="dark-lable w-18" style="float: left">The sum of Dhs:</span>
        <span class="input"></span>
        <span class="dark-lable w-18 text-end" style="float: right">Mr Hamza</span>
        <div class="clear-fix" style=" margin:0px"></div>
      </div>
      <div class="clear-fix" style=" margin:0px"></div>

      {{-- <div>
        <div style="float:left;width:33%">

          <div>
            <span style="background:black;color:white;padding:10px 5px; display:inline-block">Chash/Cheque No.</span>
            <span style="height: 45px;width:100px;border:1px solid black;display:inline-block;margin:0px"></span>
          </div>

        </div>
        <div style="float:left;width:33%">
          sds
        </div>
        <div style="float:left;width:33%"></div>
      <div class="clear-fix" style=" margin:0px"></div>

      </div>
      <div class="clear-fix" style=" margin:0px"></div> --}}


      <div class="input-group"> 
        <span class="dark-lable w-18" style="float: left;">Being:</span>
        <span class="dark-lable w-18 text-end" style="float: right;">Mr Hamza</span>
        <div class="clear-fix" style="margin:0px"></div>

        <span class="light-lable w-18" style="float: left;margin-left:-1px;border-right: 1px solid black;"></span>
        <span class="light-lable w-18 text-end" style="float: right;margin-right:-1px;border-left: 1px solid black;"></span>
        <div class="clear-fix" style="margin:0px"></div>
      </div>

    </div>  


    <div class="footer">
      <div style="float: left; width:10%"></div>
      <div style="float: left; width:28%">
        <div style="border: 1px solid black;height:150px; padding:0px;">
          <div style="background:black; color:white;margin:0px; padding:10px 5px; ">
            <span style="">Receiver's Sign</span>
            <span style="text-align:right"> Sign</span>
          </div>

        </div>
      </div>
      <div style="float: left; width:13%"></div>

      <div style="float: left; width:28%">

        <div style="border: 1px solid black;height:150px; padding:0px;">
          <div style="background:black; color:white;margin:0px; padding:10px 5px; ">
            <span style="">Cashier</span>
            {{-- <span style="text-align:right"> Sign</span> --}}
          </div>

        </div>

      </div>
      <div style="float: left; width:20%;">
        <img src="{{ public_path('assets/pdf/receipt_voucher/qr_code.svg') }}" alt="" class="qr_code">
      </div>
    </div>
    <div class="clear-fix" style="margin:0px"></div>

    <div class="fix-footer">
      <h4>Thank You for your Business!</h4>
    </div>
</body>
</html>