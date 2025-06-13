<?php
/**
 * Template Name: BB Theme - Portföy
 * @package Fox_Child
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php wp_title( '|', true, 'right' ); ?><?php bloginfo( 'name' ); ?></title>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

	<header class="site-header">
        <div class="header-content">
			<div class="header-left">
                <div class="logo">
                    <a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
                        <?php if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) { the_custom_logo(); } else { bloginfo( 'name' ); } ?>
                    </a>
                </div>
				<nav class="primary-navigation">
					<ul>
						<li><a href="https://borsadabibasina.com/hakkinda">Hakkında</a></li>
						<li><a href="https://borsadabibasina.com/iletisim">İletişim</a></li>
						<li><a href="https://borsadabibasina.com/yasal-uyari">Yasal Uyarı</a></li>
						<li><a href="https://borsadabibasina.com/gizlilik">Gizlilik</a></li>
						<li><a href="https://borsadabibasina.com/abonelik">Abonelik Seviyeleri</a></li>
					</ul>
				</nav>
			</div>
			<div class="header-right">
				<button class="icon-button" aria-label="Search"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 19C15.4183 19 19 15.4183 19 11C19 6.58172 15.4183 3 11 3C6.58172 3 3 6.58172 3 11C3 15.4183 6.58172 19 11 19Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
				<a href="https://borsadabibasina.com/hesabim" class="subscribe-button">Hesabım</a>
				<button class="theme-toggle-button icon-button" aria-label="<?php esc_attr_e( 'Temayı Değiştir', 'fox-child' ); ?>">🌙</button>
			</div>
		</div>
	</header>

<?php get_template_part('template-parts/ticker-bar'); ?>

	<div class="main-container portfolio-page-container">

		<section class="content-header">
			<nav class="secondary-navigation">
				<?php if ( has_nav_menu( 'bb_secondary_menu' ) ) { wp_nav_menu( array( 'theme_location' => 'bb_secondary_menu', 'container' => false, 'menu_class' => 'secondary-nav-list', 'depth' => 1 ) ); } else { echo '<ul class="secondary-nav-list"><li><a href="#">' . esc_html__( 'Menü Ata', 'fox-child' ) . '</a></li></ul>'; } ?>
			</nav>
		</section>

        <?php
        if ( have_posts() ) :
            while ( have_posts() ) : the_post();

                // 1. WooCommerce'in kararını bir değişkene al.
                ob_start();
                the_content();
                $original_content = ob_get_clean();

                // 2. İçerikte kısıtlama mesajı var mı diye kontrol et.
                $is_restricted_by_plugin = ( strpos( $original_content, 'wc-memberships-content-restricted' ) !== false );

                if ( $is_restricted_by_plugin ) {
                    // --- ERİŞİM YOK: Eklentinin oluşturduğu mesajı bas. ---
                    // Portföy sayfasının sidebar'ı olmadığı için doğrudan main içinde basabiliriz.
                    echo '<main class="main-column">';
                    echo $original_content;
                    echo '</main>';
                } else {
                    // --- ERİŞİM VAR: Portföy sayfasının içeriğini göster. ---
                    
                    // --- Portföy verilerini hazırla ---
                    $portfolio_options = get_option( 'fox_child_portfolio_options' );
                    $stats = $portfolio_options['stats'] ?? [];
                    $total_return_text = $stats['total_return_text'] ?? 'N/A';
                    $benchmark_outperformance_text = $stats['benchmark_outperformance_text'] ?? 'N/A';
                    $real_return_text = $stats['real_return_text'] ?? 'N/A';
                    $last_updated_date_raw = $stats['last_updated_date'] ?? '';
                    $last_updated_date = !empty($last_updated_date_raw) ? date_i18n( get_option( 'date_format' ), strtotime( $last_updated_date_raw ) ) : date_i18n( get_option( 'date_format' ) );
                    $assets = $portfolio_options['assets'] ?? [];
                    $total_portfolio_value = 0;
                    if (!empty($assets)) {
                        foreach ($assets as $asset) {
                            $quantity = $asset['quantity'] ?? 0;
                            $current_price = $asset['current_price'] ?? 0;
                            if (is_numeric($quantity) && is_numeric($current_price)) {
                                $total_portfolio_value += floatval($quantity) * floatval($current_price);
                            }
                        }
                    }
                    
                    // --- Sayfanın Kendi İçeriğini (Giriş Metni vb.) Göster ---
                    // $original_content, kısıtlama mesajı içermediği için burada güvenle kullanılabilir.
                    if( !empty(trim($original_content)) ) {
                       echo '<header class="page-header portfolio-header">';
                       echo '<div class="page-content portfolio-intro">';
                       echo do_shortcode($original_content);
                       echo '</div>';
                        echo '</header>';
                    }
                    
                    // --- PORTFÖY GÖRSEL ALANINI OLUŞTUR ---
                    ?>
                    <div class="content-body portfolio-content-layout">
                        <div class="portfolio-layout-primary">
                            <section class="portfolio-section portfolio-stats-section">
                                <h2 class="portfolio-section-title">Genel Durum</h2>
                                <div class="stats-container">
                                <div class="stat-item"><strong>Toplam Getiri</strong><br><span id="toplamGetiri"><?php echo esc_html( $total_return_text ); ?></span></div>
                                <div class="stat-item"><strong>Benchmark Üzeri</strong><br><span id="benchmarkUzeri"><?php echo esc_html( $benchmark_outperformance_text ); ?></span></div>
                                <div class="stat-item"><strong>Reel Getiri</strong><br><?php echo esc_html( $real_return_text ); ?></div>
                                <div class="stat-item"><strong>Son Güncelleme</strong><br><?php echo esc_html( $last_updated_date ); ?></div>
                                </div>
                            </section>
                            <section class="portfolio-section portfolio-chart-section">
                                <h2 class="portfolio-section-title">BBB vs Benchmark (Kümülatif Getiri)</h2>
                                <div class="chart-container"><canvas id="cumulativePerformanceChart"></canvas></div>
                            </section>
                            <section class="portfolio-section portfolio-table-section">
                                <h2 class="portfolio-section-title">Portföy Tablosu</h2>
                                <div class="table-wrapper">
                                <table class="portfolio-table">
                                    <thead>
                                    <tr>
                                        <th class="col-left"><?php esc_html_e( 'Varlık Sınıfı', 'fox-child' ); ?></th>
                                        <th class="col-left"><?php esc_html_e( 'Sembol', 'fox-child' ); ?></th>
                                        <th class="col-left"><?php esc_html_e( 'Varlık', 'fox-child' ); ?></th>
                                        <th class="col-right"><?php esc_html_e( '% Ağırlık', 'fox-child' ); ?></th>
                                        <th class="col-right"><?php esc_html_e( 'Net K/Z %', 'fox-child' ); ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ( ! empty( $assets ) ) : foreach ( $assets as $asset ) :
                                        $asset_class = $asset['class'] ?? ''; $asset_symbol = $asset['symbol'] ?? ''; $asset_name = $asset['name'] ?? '';
                                        $quantity = $asset['quantity'] ?? 0; $avg_cost = $asset['avg_cost'] ?? 0; $current_price = $asset['current_price'] ?? 0;
                                        $asset_value = (is_numeric($quantity) && is_numeric($current_price)) ? floatval($quantity) * floatval($current_price) : 0;
                                        $asset_weight = ($total_portfolio_value > 0) ? ($asset_value / $total_portfolio_value) * 100 : 0;
                                        $asset_pl = 0; $pl_class = ''; $pl_prefix = '';
                                        if (is_numeric($avg_cost) && floatval($avg_cost) > 0 && is_numeric($current_price)) { $asset_pl = ((floatval($current_price) - floatval($avg_cost)) / floatval($avg_cost)) * 100; $pl_class = (floatval($asset_pl) >= 0) ? 'gain' : 'loss'; $pl_prefix = (floatval($asset_pl) > 0) ? '+' : ''; } else { $asset_pl = 0; $pl_class = ''; } ?>
                                        <tr>
                                            <td class="col-left"><?php echo esc_html( $asset_class ); ?></td>
                                            <td class="col-left"><?php echo esc_html( $asset_symbol ); ?></td>
                                            <td class="col-left"><?php echo esc_html( $asset_name ); ?></td>
                                            <td class="col-right"><?php echo esc_html( number_format_i18n( $asset_weight, 1 ) ); ?>%</td>
                                            <td class="col-right <?php echo $pl_class; ?>"><?php echo $pl_prefix . esc_html( number_format_i18n( $asset_pl, 1 ) ); ?>%</td>
                                        </tr>
                                    <?php endforeach; else : echo '<tr><td colspan="5">' . esc_html__( 'Portföy varlığı girilmemiş.', 'fox-child' ) . '</td></tr>'; endif; ?>
                                    </tbody>
                                </table>
                                </div>
                            </section>
                        </div>
                        <div class="portfolio-layout-secondary">
                            <section class="portfolio-section portfolio-pie-chart-section">
                                <h2 class="portfolio-section-title chart-title">Portföy Dağılımı</h2>
                                <div class="chart-container"><canvas id="pieChart"></canvas></div>
                            </section>
                                <section class="portfolio-section portfolio-bar-chart-section">
                                <h2 class="portfolio-section-title chart-title">Aylık Performans</h2>
                                    <div class="chart-container"><canvas id="monthlyPerformanceChart"></canvas></div>
                            </section>
                        </div>
                    </div>
                    <?php
                }

            endwhile;
        endif;
        ?>
	</div>

	<?php wp_footer(); ?>
</body>
</html>