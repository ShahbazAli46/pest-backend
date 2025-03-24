<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Service Report</title>
    <style>
        * {
            margin: 1mm;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        body{
            border: 1px solid gray;
        }
        .clear-fix{
            clear: both;
        }
        .header-logo .heading-logo{
            float: left;
            width: 60%;
            margin: 0px;
            padding: 0px; 
            text-align: right;
        }
        .header-logo .heading-date{
            float: right;
            width: 39%;
            margin-top: 30px;
            text-align: right;
        }
        .heading-logo img{
            width: 275px;
            padding: 0px;
        }
       
        .header-bottom-logo .logo-div{
            float: left;
            width: 60%;
            margin: 0px;
            margin-bottom: -35px;
        }
        .header-bottom-logo .client-info{
            float: right;
            width: 39%;
            margin-top: -20px;
            /* line-height: 1.5; */
        }
        .logo-div .accurate-logo{
            width: 330px;
            margin-top: -100;
        }

        .logo-div .approved-logo{
            width: 225px;
        }
        .content{
          padding: 0 15px;
        }
        

      /* .form-container {
        margin: 0 auto;
        border: 1px solid #ccc;
        background-color: white;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      } */

      /* .form-header {
        padding: 0 15px;
      } */

      .header-row {
        /* display: flex; */
        align-items: center;
        flex-wrap: nowrap;
        white-space: nowrap;
        overflow-x: auto;
      }

      .form-title {
        font-weight: bold;
        margin-right: 10px;
        display: inline;
      }

      .visit-type {
        display: inline;
        align-items: center;
        font-size: 12px;
      }

      .time-field {
        display: inline-block;
        margin-left: 2px;
        font-size: 13px;
        margin-bottom: -5px;
      }


      /* Table Styling */
      .inspection-table {
          width: 100%;
          border-collapse: collapse;
          border: 2px solid #000;
      }
      
      .inspection-table th, 
      .inspection-table td {
          border: 1px solid #000;
          padding: 5px;
          vertical-align: top;
      }
      
      .inspection-table th {
          background-color: #f2f2f2;
          font-weight: bold;
          text-align: center;
      }
      .infestation-level {
          /* margin: 5px 0; */
          margin: 0px;
          font-size: 12px;
          position: relative;
      }
      
      .checkbox-container {
          display: inline-block;
          margin-left: 10px;
          margin-bottom: 0px;
          margin-top: 1px;
          padding-top: 1px;
          padding-bottom: 0px;
      }

      .checkbox-container .checkbox{
        position: absolute;
        right: 15px;;
      }

      .infestation-column {
          width: 12%;
      }
      
      .area-column {
          width: 20%;
      }
      
      .pests-column {
          width: 15%;
      }
      
      .details-column {
          width: 53%;
      }
      
      .main-infested-label {
          font-weight: bold;
          padding: 5px;
      }
      .recommendations{
        text-decoration: underline;
      }
      .recom-text{
        font-size: 11px;
        line-height: 1;
      }
      .footer{
        font-size: 12px;
        margin-top: 10px;
        font-weight: bold
      }
      .icon img{
        width:15px;
        margin-bottom: -2px;
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

    </style>
</head>
<body>
    <div class="header-logo">
        <div class="heading-logo">
            <img src="{{ public_path('assets/pdf/service_report/heading_logo.svg') }}" alt="Heading Logo">
        </div>
        <div class="heading-date">
            <p>No. Date:_______________________</p>
        </div>
        <div class="clear-fix"></div>
    </div>

    <div class="header-bottom-logo">
        <div class="logo-div">
            <img src="{{ public_path('assets/pdf/service_report/accurate.svg') }}" class="accurate-logo" alt="Accurate Pest Control Services LLC">
            <img src="{{ public_path('assets/pdf/service_report/approved_by_logo.svg') }}" class="approved-logo" alt="Approved By Logo">
        </div>
        <div class="client-info">
            <p>Client Name:__________________________________</p>
            <p>Facility Covered:_______________________________</p>
            <p>Address:______________________________________</p>
            <p>Contact No:___________________________________</p>
        </div>
        <div class="clear-fix"></div>
    </div>

    <div class="content">
      
      <div class="form-header">
        <div class="header-row">
          <div class="form-title">Type of Visits:</div>
          <div class="visit-type">
            Regular Treatment (Contract)<span class="checkbox"></span>
          </div>
          <div class="visit-type">
            Inspection Visit (Contract)<span class="checkbox"></span>
          </div>
          <div class="visit-type">
            Complain (Contract)<span class="checkbox"></span>
          </div>
          <div class="visit-type">
            One Time T<span class="checkbox"></span>
          </div>
          <div class="visit-type">
            Complain (OTT)<span class="checkbox "></span>
          </div>
          <div class="time-field">Time in............ Time out............</div>
        </div>
      </div>

      <table class="inspection-table">
        <tr>
            <th class="area-column">Inspected Areas<br>(Premises Covered)</th>
            <th class="pests-column">Pests Found</th>
            <th class="infestation-column">Infestation<br>Level</th>
            <th class="details-column">Report Details & Follow Up Details</th>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>
                <div class="infestation-level">
                    <span class="checkbox-container" >
                      Low  <span class="checkbox"></span>
                    </span>
                </div>
                <div class="infestation-level">
                    <span class="checkbox-container">
                      Medium <span class="checkbox"></span>
                    </span>
                </div>
                <div class="infestation-level">
                    <span class="checkbox-container">
                      High  <span class="checkbox"></span>
                    </span>
                </div>
            </td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>
                <div class="infestation-level">
                    <span class="checkbox-container">
                      Low <span class="checkbox"></span>
                    </span>
                </div>
                <div class="infestation-level">
                    <span class="checkbox-container">
                      Medium <span class="checkbox"></span>
                    </span>
                </div>
                <div class="infestation-level">
                    <span class="checkbox-container">
                      High <span class="checkbox"></span>
                    </span>
                </div>
            </td>
            <td rowspan="2">Special Recommendations:</td>
        </tr>
        <tr>
            <td class="main-infested-label">Main Infested Areas</td>
            <td colspan="2"></td>
        </tr>
      </table>

      <table class="inspection-table" >
        <tr>
          <th class="">Premises Sered For:</th>
          <th class="">Type of Treatment</th>
          <th class="">Chemical & Marterial Used</th>
          <th class="">Dose</th>
          <th class="">Quantity</th>
        </tr>
        <tr style="">
          <td rowspan="5" style="padding-top:0px;padding-bottom:0px">
            <div style="float: left; width:49%">
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Roache <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Fruit Flies <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Store Insect <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Drain Flies <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      House Flies <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Termite <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Snakes <span class="checkbox"></span>
                  </span>
              </div>
            </div>
            <div style="float: right; width:49%">
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Mosquito <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Bedbug <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Ants <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Rats <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Birds <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Lizards <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Others <span class="checkbox"></span>
                  </span>
              </div>
            </div>
            <div class="clear-fix"></div>
          </td>
          <td rowspan="5" style="padding-top:0px;padding-bottom:0px">
            <div style="float: left; width:49%;">
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Spray T <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Fogging <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Fumigation <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Mechanical T <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Termite T <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Other: <span class="checkbox"></span>
                  </span>
              </div>
            </div>
            <div style="float: right; width:49%;">
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Gel T <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Mist <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      ULV <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Dust <span class="checkbox"></span>
                  </span>
              </div>
              <div class="infestation-level">
                  <span class="checkbox-container">
                      Birds Control <span class="checkbox"></span>
                  </span>
              </div>
            </div>
            <div class="clear-fix"></div>
          </td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td></td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td colspan="2" rowspan="2" style="padding-top: 0px; padding-bottom:0px;">
            <h5 class="recommendations">Recommendations and Remarks:</h5>
            <p class="recom-text">Keep the Gel in place and avoid washing with water in the treated areas.</p>
            <p class="recom-text">Keep the GPC Treated area closed for at least 4 hours.</p>
            <p class="recom-text">Maintain a regular cleaning for the facility and specially for the infected areas</p>
            <p class="recom-text">Close any gaps and do the needed maintenance jobs eliminate the nesting of pests and entering of RATS.</p>
            <p class="recom-text">Follow the recommendations and directions given by the team to minimize the infestation or prevent future pests problems.</p>
          </td>
          <td class="main-infested-label" style="text-align: center;">Client Signature</td>
          <td colspan="2"></td>
        </tr>
        <tr>
          <td class="main-infested-label" style="text-align: center;">Accurate pest control services LLC <br>Supervisor Name & Signature</td>
          <td colspan="2"></td>
        </tr>
      </table>

      <div class="footer">
        <div style="float:left;width:63%;">
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/phone.svg') }}" alt=""></span> +971 52 152 8725
          </span>
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/landline.svg') }}" alt=""></span>  06 5422661
          </span>
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/website.svg') }}" alt=""></span> www.accuratepestcontrol.ae
          </span>
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/email.svg') }}" alt=""></span> info@accuratepestcontrol.ae
          </span>
          <br>
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/location.svg') }}" alt=""></span> Building No. 3702, Shop No. 3 & 4, Sharjah - UAE
          </span>
        </div>
        <div style="float:right;width:36%;">
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/location.svg') }}" alt=""></span> Warehouse No. 1, Al Qusais Industrial Area 4, Dubai - UAE
          </span>
          <br>
          <span>
            <span class="icon"><img src="{{ public_path('assets/pdf/service_report/location.svg') }}" alt=""></span> Shop No. 1, Plot No. 3074, Ajman - UAE
          </span>
        </div>
        <div class="clear-fix"></div>
      </div>
    </div>
    
</body>
</html>