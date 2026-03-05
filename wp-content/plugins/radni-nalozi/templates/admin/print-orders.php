<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php _e('Štampa radnih naloga', 'radni-nalozi'); ?></title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
        }
        
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }
        
        .print-order {
            page-break-after: always;
            padding: 10mm 0;
        }
        
        .print-order:last-child {
            page-break-after: avoid;
        }
        
        .print-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        
        .print-header h1 {
            font-size: 24pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .print-header .order-number {
            font-size: 14pt;
            color: #666;
        }
        
        .print-header .order-date {
            font-size: 10pt;
            color: #999;
        }
        
        .print-section {
            margin-bottom: 20px;
        }
        
        .print-section h3 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
            text-transform: uppercase;
            color: #333;
        }
        
        .customer-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
        }
        
        .customer-info p {
            margin: 5px 0;
        }
        
        .customer-info strong {
            display: inline-block;
            width: 120px;
        }
        
        .print-item {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            border-left: 4px solid #2563eb;
            display: flex;
            flex-wrap: wrap;
        }
        
        .print-item-details {
            flex: 1;
            min-width: 250px;
        }
        
        .print-item-details h4 {
            font-size: 14pt;
            margin-bottom: 10px;
            color: #2563eb;
        }
        
        .print-item-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        
        .print-item-info p {
            margin: 0;
        }
        
        .print-item-info strong {
            color: #666;
        }
        
        .print-item-note {
            grid-column: 1 / -1;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ccc;
            font-style: italic;
        }
        
         /* LAYOUT: Glavna levo, ostale desno */
        .print-item-images {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            margin-left: 20px;
            max-width: 450px;
            align-items: flex-start;
        }
         /* Glavna slika - velika, levo */
        .print-item-image.main-image {
            width: 200px;
            height: 200px;
            overflow: hidden;
            border-radius: 8px;
            border: 3px solid #fbbf24;
            background: rgba(128, 128, 128, 0.4); /* 40% siva za transparentne slike */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex: 0 0 200px;
        }
                /* Ostale slike - male, desno */
        .print-item-images-small {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-content: flex-start;
        }

        .print-item-image {
            width: 80px;
            height: 80px;
            overflow: hidden;
            border-radius: 5px;
            border: 2px solid #ddd;
            background: rgba(128, 128, 128, 0.4); /* 40% siva za transparentne slike */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            flex: 0 0 80px;
        }
        
        .print-item-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        /* Zvezdica na glavnoj slici */
        .print-item-image .main-badge {
            position: absolute;
            top: 5px;
            right: 5px;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            color: #fff;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            line-height: 1;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
            font-weight: bold;
        }
        
        /* Za štampu - prikaži sve slike */
        @media print {
            .print-item-images {
                page-break-inside: avoid;
            }

            .print-item-image,
            .print-item-image.main-image {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background: rgba(128, 128, 128, 0.4) !important;
            }
            .print-item-image {
                border-color: #999;
            }

            .print-item-image.main-image {
                border-color: #fbbf24;
            }
        }
        
        .print-total {
            text-align: right;
            font-size: 14pt;
            font-weight: bold;
            padding: 15px;
            background: #2563eb;
            color: #fff;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .print-footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            font-size: 10pt;
            color: #999;
        }
        
        .no-print {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: #333;
            color: #fff;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9999;
        }
        
        .no-print button {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 10px 25px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .no-print button:hover {
            background: #1d4ed8;
        }
        
        .no-print .info {
            font-size: 14px;
        }
         /* Za štampu */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .print-container {
                padding: 0;
            }
            
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .print-item {
                break-inside: avoid;
            }
        }
        
        @media screen {
            body {
                background: #eee;
                padding-top: 70px;
            }
            
            .print-order {
                background: #fff;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                margin-bottom: 30px;
                padding: 20mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <div class="info">
            <?php printf(__('Prikazano %d radnih naloga za štampu', 'radni-nalozi'), count($orders_data)); ?>
        </div>
        <button onclick="window.print();"><?php _e('Štampaj', 'radni-nalozi'); ?></button>
    </div>
    
    <div class="print-container">
        <?php foreach ($orders_data as $order): ?>
            <div class="print-order">
                <div class="print-header">
                    
                    <div class="order-number"><h1><?php _e('Nalog:', 'radni-nalozi'); ?> <?php echo esc_html($order->order_number); ?></h1></div>
                    <div class="order-date"><h2><?php echo date_i18n('d.m.Y H:i', strtotime($order->created_at)); ?></h2></div>
                </div>
                
                <div class="print-section">
                    <h3><?php _e('Podaci o kupcu', 'radni-nalozi'); ?></h3>
                    <div class="customer-info">
                        <p><strong><?php _e('Ime:', 'radni-nalozi'); ?></strong> <?php echo esc_html($order->customer_name); ?></p>
                        <p><strong><?php _e('Adresa:', 'radni-nalozi'); ?></strong> <?php echo esc_html($order->customer_address); ?>, <?php echo esc_html($order->customer_postal); ?> <?php echo esc_html($order->customer_city); ?></p>
                        <p><strong><?php _e('Telefon:', 'radni-nalozi'); ?></strong> <?php echo esc_html($order->customer_phone); ?></p>
                    </div>
                </div>
                
                <div class="print-section">
                    <h3><?php _e('Stavke naloga', 'radni-nalozi'); ?></h3>
                    
                    <?php foreach ($order->items as $item): ?>
                        <div class="print-item">
                            <div class="print-item-details">
                                <h4><?php echo esc_html($item->print_name); ?></h4>
                                <div class="print-item-info">
                                    <p><strong><?php _e('Tip:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->garment_type ?? 'Majica'); ?></p>
                                    <p><strong><?php _e('Pol:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->category ?? 'Muška'); ?></p>
                                    <p><strong><?php _e('Boja:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->color); ?></p>
                                    <p><strong><?php _e('Veličina:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->size); ?></p>
                                    <p><strong><?php _e('Količina:', 'radni-nalozi'); ?></strong> <?php echo intval($item->quantity); ?> kom.</p>
                                    <p><strong><?php _e('Cena:', 'radni-nalozi'); ?></strong> <?php echo number_format($item->price, 2, ',', '.'); ?> RSD</p>
                                    
                                    <?php if ($item->note): ?>
                                        <div class="print-item-note">
                                            <strong><?php _e('Napomena:', 'radni-nalozi'); ?></strong> <?php echo esc_html($item->note); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php
                            // Uzmi SVE slike za ovu stavku
                            $item_images = RN_Orders::get_item_images($item->id);
                            if (!empty($item_images)):
                                // Odvojimo glavnu sliku od ostalih
                                $main_image = null;
                                $other_images = [];
                                
                                foreach ($item_images as $img) {
                                    if ($img->is_main) {
                                        $main_image = $img;
                                    } else {
                                        $other_images[] = $img;
                                    }
                                }
                                
                                // Ako nema glavne, uzmi prvu
                                if (!$main_image && !empty($item_images)) {
                                    $main_image = $item_images[0];
                                    $other_images = array_slice($item_images, 1);
                                }
                            ?>
                                <div class="print-item-images">
                                    <?php if ($main_image): ?>
                                        <!-- Glavna slika - velika -->
                                        <div class="print-item-image main-image">
                                            <img src="<?php echo esc_url($main_image->image_url); ?>" alt="<?php echo esc_attr($main_image->image_title ?: $item->print_name); ?>">
                                            <span class="main-badge">★</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($other_images)): ?>
                                        <!-- Ostale slike - male -->
                                        <div class="print-item-images-small">
                                            <?php foreach ($other_images as $img): ?>
                                                <div class="print-item-image">
                                                    <img src="<?php echo esc_url($img->image_url); ?>" alt="<?php echo esc_attr($img->image_title ?: $item->print_name); ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($item->image_url): ?>
                            
                            
                                <!-- Fallback za stari format (jedna slika) -->
                                <div class="print-item-images">
                                    <div class="print-item-image">
                                        <img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->print_name); ?>">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="print-total">
                    <?php _e('UKUPAN IZNOS:', 'radni-nalozi'); ?> <?php echo number_format($order->total_amount, 2, ',', '.'); ?> RSD
                </div>
                
                <div class="print-footer">
                    <div><?php _e('Korisnik:', 'radni-nalozi'); ?> <?php echo $order->user ? esc_html($order->user->display_name) : '-'; ?></div>
                    <div><?php echo date_i18n('d.m.Y H:i'); ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
        if (window.opener) {
        }
    </script>
</body>
</html>
