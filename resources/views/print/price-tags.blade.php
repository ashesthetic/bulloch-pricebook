<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Price Tags</title>
    <style>
        @page {
            size: 612pt 792pt;
            margin: 35.5pt 16.5pt 34.5pt 17.5pt;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
        }

        /* Simulate page margins in browser — @page only kicks in during print */
        @media screen {
            body {
                margin: 35.5pt 16.5pt 34.5pt 17.5pt;
                background: #e5e5e5;
            }
            table {
                background: #fff;
                box-shadow: 0 0 8px rgba(0,0,0,0.2);
            }
        }

        table {
            border-collapse: collapse;
            width: 578pt;
            table-layout: fixed;
        }

        td {
            width: 144.5pt;
            height: 90.25pt;
            padding: 0;
            border: none;
            vertical-align: top;
            overflow: hidden;
        }

        .tag-inner {
            box-sizing: border-box;
            width: 144.5pt;
            height: 90.25pt;
            padding: 6pt 5pt 3pt 5pt;
        }

        .tag-name {
            display: block;
            font-size: 9pt;
            font-weight: bold;
            line-height: 1.2;
            overflow: hidden;
            white-space: nowrap;
            color: #000;
        }

        .tag-barcode {
            display: block;
            text-align: center;
            padding: 2pt 0 0 0;
        }

        .tag-upc {
            display: block;
            font-size: 6pt;
            font-family: monospace;
            text-align: center;
            color: #000;
            letter-spacing: 0.5pt;
        }

        .tag-price {
            display: block;
            font-size: 10pt;
            font-weight: bold;
            text-align: right;
            color: #000;
            line-height: 1;
            padding-top: 2pt;
        }
    </style>
</head>
<body>
    <table>
        @foreach (array_chunk($tags, 4) as $row)
            <tr>
                @foreach ($row as $tag)
                    <td>
                        @if ($tag !== null)
                            <div class="tag-inner">
                                <span class="tag-name">{{ $tag['name'] }}</span>
                                <span class="tag-barcode">
                                    @if ($tag['barcode_b64'])
                                        <img
                                            src="data:image/svg+xml;base64,{{ $tag['barcode_b64'] }}"
                                            style="display:block;width:134pt;height:44pt;margin:0 auto;"
                                            alt=""
                                        />
                                    @endif
                                </span>
                                @if ($tag['upc'])
                                    <span class="tag-upc">{{ $tag['upc'] }}</span>
                                @endif
                                <span class="tag-price">${{ $tag['price'] }}</span>
                            </div>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>
</body>
</html>
