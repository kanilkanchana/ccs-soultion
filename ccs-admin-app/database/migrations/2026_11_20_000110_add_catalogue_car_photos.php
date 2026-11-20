<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $photos = [
            'catalogue-v110-toyota-vitz-hybrid' => 'https://global.toyota/pages/news/images/2017/01/12/1330/vit1701_28_s.jpg',
            'catalogue-v110-toyota-prius-phv-prime' => 'https://www.tochigitoyota.com/file/cms/-1/9496/zvw52-ahxgb_0_30.png',
            'catalogue-v110-toyota-probox-hybrid' => 'https://global.toyota/pages/news/images/2021/12/22/1330/20211222_01_01_s.jpg',
            'catalogue-v110-toyota-corolla-sedan-hybrid' => 'https://cdn2.modera.org/resized/1152x/i/toyota-rsdm/dpackage/corolla_2/exteriors/040/b70/g11/1024x768_20.png',
            'catalogue-v110-toyota-corolla-sport-hybrid' => 'https://global.toyota/pages/news/images/2021/07/02/1330/rendition/20210702_01_05_W610_H407.jpg',
            'catalogue-v110-toyota-corolla-touring-hybrid' => 'https://manual.toyota.jp/pages/contents/ownersmanual/image/corollatouring/corollatouring_2210_sp.png',
            'catalogue-v110-toyota-yaris-hybrid' => 'https://images.toyota-europe.com/de/product-token/218ef05f-01c1-4c46-bb13-d1f2b6e026df/vehicle/40eec0b4-8b9a-4a01-9763-4186b4b8d42a/width/1000/height/533/scale-mode/1/padding/0/background-colour/FFFFFF/image-quality/75/day-exterior-3_040_FB20.webp',
            'catalogue-v110-toyota-alphard-hybrid' => 'https://www.k-toyota.co.jp/file/cms/-1/1568183/ayh30w-pfxqb_b_0_90.jpg',
            'catalogue-v110-toyota-vellfire-hybrid' => 'https://global.toyota/pages/news/images/2015/01/26/1330/velf1501_06_01_s.jpg',
            'catalogue-v110-toyota-estima-hybrid' => 'https://global.toyota/pages/news/older/images/2012/05/08/002.jpg',
            'catalogue-v110-toyota-crown-crossover' => 'https://cmjprfestaren03.blob.core.windows.net/rent-type1/sp/img/cp/23_ser_crowncp05/p_flag_01.jpg',
            'catalogue-v110-toyota-crown-sport' => 'https://www.shizuokatoyota.co.jp/file/cms/-1/6458552/azsh36w-bnxgb_0_90.jpg',
            'catalogue-v110-toyota-crown-sedan' => 'https://kinto-jp.com/cms-images/assets/c8f79fe8d67e4eb3a34ad72f0d950baf/06a480c01326465e84248ea764972e62/p008_030.jpg',
            'catalogue-v110-toyota-crown-estate' => 'https://toyota.jp/tcv/resources/crownestate/002_p_001/images/assy/1l6/azsh39w-cnxzb_0_30.png',
            'catalogue-v110-toyota-rav4-phv-prime' => 'https://global.toyota/pages/news/images/2020/06/08/1330/20200608_01_28_s.jpg',
            'catalogue-v110-toyota-innova-hycross-zenix-hybrid' => 'https://kallatoyota.co.id/storage/images/360exterior/175921449912-all-new-kijang-innova-zenix.png',
            'catalogue-v110-toyota-urban-cruiser-hyryder-hybrid' => 'https://static.caronphone.com/public/brands/33/125/867/867_1709020975.webp',
            'catalogue-v110-toyota-highlander-hybrid' => 'https://cadastro.motorleads.co/public/images/20240829064027-blanco3.png',
            'catalogue-v110-toyota-land-cruiser-prado-250' => 'https://nextcar.ua/images/blog/622/toyota_land_cruiser_250_disel_48v__1_.jpg',
            'catalogue-v110-toyota-hilux-48v' => 'https://www.timeslive.co.za/resizer/v2/JNQBNMZWZVMILEFGZZV6VARR4Y.jpg?auth=f8b6fd83fcfb0b022be8d1a49d82df859408c998ed9ec596286d647b0bb5d3c4&height=1080&smart=true&width=1756',
            'catalogue-v110-toyota-tundra-i-force-max' => 'https://www.toyotahawaii.com/on/demandware.static/-/Sites-Servco_master/default/dw85409482/images/model/TundraHybrid/360/super-white/22_TundraHybrid_Limited_SuperWhite_1.png',
            'catalogue-v110-toyota-sequoia-i-force-max' => 'https://toyotaaeropuerto.com.mx/wp-content/uploads/2023/02/azul_sequoia.png',
            'catalogue-v110-honda-hr-v-e-hev' => 'https://honda-kl.com/wp-content/uploads/2025/09/Honda-HR-V-gallery-hrv-4.png',
            'catalogue-v110-honda-freed-hybrid' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/3a/2019-2024_Honda_Freed%2B_Hybrid.jpg/1200px-2019-2024_Honda_Freed%2B_Hybrid.jpg',
            'catalogue-v110-honda-cr-v-hybrid-e-hev' => 'https://otohondatiengiang.com/OTO3602200385/files/mau_xe/honda_crv/trang_nga.webp',
            'catalogue-v110-honda-jade-hybrid' => 'https://clicccar.com/uploads/2015/01/Honda_JADE_011.jpg',
            'catalogue-v110-honda-odyssey-hybrid' => 'https://www.honda.co.jp/customer/auto/odyssey/images/img_list_odyssey.jpg',
            'catalogue-v110-honda-zr-v-e-hev' => 'https://kurukura.jp/assets/uploads/2025/05/250520-honda-zr-v-08.jpg',
            'catalogue-v110-honda-city-e-hev' => 'https://www.hondaaccess.co.th/public/img/frontback/city/back.png?v=2',
            'catalogue-v110-honda-prelude-hybrid-new' => 'https://www.de.honda.ch/content/dam/central/cars/prelude-hybrid/specifications/26YM_Prelude_ADVANCE_EU_side_stu_B_637P_001.png/_jcr_content/renditions/fb_w.webp',
            'catalogue-v110-honda-legend-hybrid' => 'https://www.honda.co.jp/auto-archive/legend/4door/2022/webcatalog/type/type/image/pic_02.jpg',
            'catalogue-v110-honda-nsx-sport-hybrid' => 'https://global.honda/content/dam/site/global-jp/news-new/cq_img/2016/08/4160825-nsx/4160825-nsx_01.jpg',
            'catalogue-v110-honda-clarity-plug-in-hybrid' => 'https://automobiles.honda.com/-/media/Honda-Automobiles/Vehicles/2021/Clarity%20Plug-In%20Hybrid/AW/Carshot/carshot_ClarityPlug-InHybrid_front_CLARITYPLUG-IN_2021_PlatinumWhitePearl_ZC5F1MEW_NH-883P.png',
            'catalogue-v110-suzuki-alto-hybrid-japan' => 'https://www.suzuki.co.jp/dealer/sj-admin/sharefiles/hybrid-s-Z2S_82e5f568-4095-11ee-955d-fa163e38a592.jpg',
            'catalogue-v110-suzuki-wagon-r-smile-hybrid' => 'https://www.suzuki.co.jp/car/wagonr_smile/detail/img/tmb-s-C2H.jpg',
            'catalogue-v110-suzuki-spacia-gear-hybrid' => 'https://www.suzuki.co.jp/car/spacia_gear/detail/img/xz-FCS.jpg',
            'catalogue-v110-suzuki-hustler-hybrid' => 'https://www.suzuki.co.jp/car/hustler/common/img/car_body/img-hustler_hybrid_g-fr2-7.png',
            'catalogue-v110-suzuki-solio-mild-hybrid' => 'https://www.suzuki.co.jp/car/solio/detail/img/mz-ZMU.jpg',
            'catalogue-v110-suzuki-solio-strong-hybrid' => 'https://www.suzuki.co.jp/dealer/sj-admin/sharefiles/postdata/blogs.dir/2564/files/2018/07/fd24bdbb13f32b23366788b167104ce0.jpg',
            'catalogue-v110-suzuki-ignis-hybrid' => 'https://media.umbraco.io/suzuki-gb/inmmcfzw/suzuki-ignis-hero-4x3.jpg',
            'catalogue-v110-suzuki-xbee-crossbee-hybrid' => 'https://www.suzuki.co.jp/car/xbee/home/img/2509/price/hybridmz/1/7.png',
            'catalogue-v110-suzuki-escudo-hybrid' => 'https://autoreview.ru/images/gallery/%D0%9D%D0%BE%D0%B2%D0%BE%D1%81%D1%82%D0%B8/2022/April/22/suzuki-escudo2.jpg',
            'catalogue-v110-suzuki-grand-vitara-smart-hybrid' => 'https://mc.bajajfinserv.in/media/catalog/product/m/a/marutisuzukigrandvitaraatarcticwhite_base.jpeg',
            'catalogue-v110-suzuki-grand-vitara-strong-hybrid' => 'https://www.drivespark.com/img/1200x60x675/2022/07/maruti-suzuki-grand-vitara-1659020231.jpg',
            'catalogue-v110-suzuki-ertiga-smart-hybrid' => 'https://www.suzuki.co.th/upload/description/20240314_s_CoXg996jJs.jpeg',
            'catalogue-v110-suzuki-vitara-hybrid' => 'https://ingrid-configurator.imgix.net/hcms/v3.3/entity/gallery/2327626/storage/MjMyNzY0MS8wL21hc3Rlci8vLzIyMjc3Nzcw?auto=format&fit=crop&h=1378&w=1920',
            'catalogue-v110-suzuki-s-cross-hybrid' => 'https://ingrid-configurator.imgix.net/hcms/v3.3/entity/gallery/2327132/storage/MjMyNzE5NC8wL21hc3Rlci8vLzIyMjc0Mzg3?auto=format&fit=crop&h=624&w=1110',
            'catalogue-v110-suzuki-baleno-hybrid' => 'https://stimg.cardekho.com/images/car-images/930x620/Maruti/Baleno/10492/1689920575521/224_arctic-white_ffffff.jpg?tr=w-898',
            'catalogue-v110-suzuki-ciaz-shvs' => 'https://3.bp.blogspot.com/-p4DQl2XrA4A/VeW1HU18QeI/AAAAAAAAFkU/06JjIRMkeUc/s1600/3.jpg',
            'catalogue-v110-suzuki-brezza-smart-hybrid' => 'https://www.marutisuzuki.com/engage/images/technology/hybrid/progressive-smart/brezza.png',
            'catalogue-v110-suzuki-xl6-smart-hybrid' => 'https://images.91wheels.com/assets/b_images/main/models/profile/profile1749465397.jpg',
            'catalogue-v110-suzuki-xl7-hybrid' => 'https://cms.suzukihyperlocal.com/read-file?path=images%2Fcar-color%2F62%2F1785332800_6a6a0440b7128.png',
            'catalogue-v110-suzuki-fronx-smart-hybrid' => 'https://www.carandbike.com/_next/image?q=75&url=https%3A%2F%2Fimages.carandbike.com%2Fcar-images%2Fcolors%2Fmaruti-suzuki%2Ffronx%2Fmaruti-suzuki-fronx-arctic-white.jpg%3Fv%3D1682341309&w=750',
            'catalogue-v110-suzuki-across-plug-in-hybrid' => 'https://suzuki.pl/assets/img/auto/ACROSS_2024/ACROSS_2024_color_070_back.webp',
            'catalogue-v110-lexus-hs-250h' => 'https://www.carsinvasion.com/gallery/2010-lexus-hs-250h/2010-lexus-hs-250h-03.jpg',
            'catalogue-v110-lexus-is-300h' => 'https://lexustokyo.jp/file/cms/99101/4296911/image.png',
            'catalogue-v110-lexus-gs-450h' => 'https://global.toyota/pages/news/images/2014/08/21/1330/20140821_01_01_S.jpg',
            'catalogue-v110-lexus-gs-300h' => 'https://global.toyota/pages/news/presssite/images/2013/10/17/gsj/gsj1310_05_s.jpg',
            'catalogue-v110-lexus-ls-600h-600hl' => 'https://global.toyota/pages/news/images/2014/10/23/1400/lshj1309_01.jpg',
            'catalogue-v110-lexus-ls-500h' => 'https://global.toyota/pages/news/images/2017/10/19/1300/lsj1710_046_s.jpg',
            'catalogue-v110-lexus-rx-400h' => 'https://upload.wikimedia.org/wikipedia/commons/e/ec/Lexus-RX400h_Crystal_White_Mica.jpg',
            'catalogue-v110-lexus-rx-350h' => 'https://lexustokyo.jp/file/cms/99101/6583418/image.jpg',
            'catalogue-v110-lexus-rx-500h' => 'https://www.lexus.co.nz/content/dam/lexus-v3-new-zealand/promotions/rx-rsp/TOY8831%20Q2%20Retail%20Webtiles_Home%20Page_Desktop_1920x1080px_v13.jpg',
            'catalogue-v110-lexus-rx-450h' => 'https://scene7.toyota.eu/is/image/toyotaeurope/RX450KINTOOptimised%3AMedium-Landscape?op_usm=1.75%2C0.3%2C2%2C0&resMode=sharp2&ts=0',
            'catalogue-v110-lexus-nx-350h' => 'https://images.lexus-europe.com/fi/product-token/b03eba36-515b-4bf0-ba62-a716f9a6190b/vehicle/1870bfd9-4d9d-4f9c-b578-767c6df8b6a4/width/1600/height/900/scale-mode/1/padding/12/background-colour/FFFFFF/image-quality/75/day-exterior-13_083_EA41.webp',
            'catalogue-v110-lexus-nx-450h' => 'https://www.lexus.fi/content/dam/lexus/nmsc/finland/kampanjat/kuvat/nopean-toimituksen-autot-2023/nx/NX-CP_1J7_EA21.jpg.thumb.1280.1280.png',
            'catalogue-v110-lexus-ux-250h' => 'https://www.lexus.fi/content/dam/lexus/nmsc/finland/kampanjat/kuvat/nopean-toimituksen-autot-2023/ux/UX-Business_1J7_FA20.jpg.thumb.1280.1280.png',
            'catalogue-v110-lexus-ux-300h' => 'https://lexus.jp/models/ux/images/features/exterior/tcv/1J7/1J7_150.png?ver=1782785615312',
            'catalogue-v110-lexus-lbx-hybrid' => 'https://lexus.jp/models/lbx/images/features/price_package/card/card05_rrbes.jpg?ver=1775048675555',
            'catalogue-v110-lexus-lm-350h' => 'https://lexus-mongolia.mn/uploaded/images/2025/Nov/1-LM_white.png',
            'catalogue-v110-lexus-lm-500h' => 'https://lexus.jp/tcv/lm500h/001_p_002/images/exterior/TAWH15W-LPTVT/085_30.png',
            'catalogue-v110-lexus-lc-500h' => 'https://global.toyota/pages/news/images/2017/03/16/1330/lcj1703_081_s.jpg',
            'catalogue-v110-lexus-lx-700h' => 'https://lexus.jp/tcv/lx700h/001_p_001/images/exterior/VJH310W-GKVLZ/223_30.png',
            'catalogue-v110-lexus-tx-500h-550h' => 'https://tmna.assetscs.toyota.com/is/image/lexusaemcs/lexus/images/models/tx/2026/visualizer/550h-plus/exterior/22-in-multispoke-alloy-wheels-with-silver-finish/wind-chill-pearl/large-2.jpg?extend=-378%2C-507%2C-378%2C-507&hei=600&qlt=100&wid=750',
            'catalogue-v110-nissan-note-aura-e-power' => 'https://www.nissan.co.jp/OPTIONAL-PARTS/NAVIOM/NOTE_SPECIAL/AURA/2508/VR/EX/IMG/ex_27.png',
            'catalogue-v110-nissan-x-trail-hybrid-t32' => 'https://www.nissan.co.jp/OPTIONAL-PARTS/NAVIOM/X-TRAIL_HV_SPECIAL/1705/VR/EX/IMG/ex_8.png',
            'catalogue-v110-nissan-dayz-hybrid' => 'https://d1uzk9o9cg136f.cloudfront.net/f/16783018/rc/2019/03/26/9c448a9c09421ba38377d3c07c8f08ace5646d43_xlarge.jpg',
            'catalogue-v110-nissan-roox-hybrid' => 'https://area.nissan.co.jp/fair/ni-tochigi/25/10/roox/img/model/post/img-roox_twotone_blue.png',
            'catalogue-v110-nissan-skyline-350gt-hybrid' => 'https://history.nissan.co.jp/SKYLINE/V37/1412/www.nissan-cdn.net/content/dam/Nissan/jp/vehicles/SKYLINE_DEV/1611/exterior_interior/C_20_4_RADIANT_20RED.jpg.ximg.s_12_h.smart.jpg',
            'catalogue-v110-nissan-fuga-hybrid' => 'https://www.planetcarsz.com/img/carros/2024/01/nissan-fuga-2011-01-20240117111233-1024x681.jpg',
            'catalogue-v110-nissan-altima-hybrid' => 'https://platform.cstatic-images.com/in/v2/stock_photos/0b77efb8-ddcf-463c-90c0-f5a84f2148fb/95106939-0909-4645-bb32-0d3edbaa3c3f.png',
            'catalogue-v110-nissan-qashqai-e-power' => 'https://storyblok-assets.prod.nissan.eu/f/288274551197823/1350x1080/7472748cf4/qq_offers.jpg/m/1350x0/filters%3Aquality%2875%29%3Aformat%28webp%29',
            'catalogue-v110-nissan-juke-hybrid' => 'https://www.rabljena-vozila.renault.ba/foto/rabljena_vozila/7025-32696-5-1280x960-ut5yk.jpg',
            'catalogue-v110-nissan-cima-hybrid' => 'https://www.nissan-cdn.net/content/dam/Nissan/jp/vehicles/CIMA_DEV/specifications/grade/vip_exterior.jpg.ximg.l_full_m.smart.jpg',
            'catalogue-v110-nissan-elgrand-e-power' => 'https://www1.nissan.co.jp/CREDIT/BVC/ELGRAND/IMAGES/g_e4.jpg',
            'catalogue-v110-mitsubishi-ek-x-ek-cross' => 'https://www.mitsubishi-motors.co.jp/lineup/ek_x/grade/img/t_premium/car_02.png',
            'catalogue-v110-mitsubishi-ek-space' => 'https://www.mitsubishi-motors.co.jp/lineup/ek_space/grade/img/m/car_04.png',
            'catalogue-v110-mitsubishi-delica-mini' => 'https://www.mitsubishi-motors.co.jp/lineup/delica_mini/grade/img/t_premium_delimaru/car_01.png',
            'catalogue-v110-mitsubishi-eclipse-cross-phev' => 'https://www.mitsubishi-motors.com/en/newsrelease/images/download/2020/12/p_201204_m_1304_img_03.png',
            'catalogue-v110-mitsubishi-delica-d-2-hybrid' => 'https://www.mitsubishi-motors.co.jp/lineup/delica_d2/grade/img/hybrid_mz_navi_pkg/car_02.png',
            'catalogue-v110-mitsubishi-ek-wagon-mild-hybrid' => 'https://www.mitsubishi-motors.co.jp/lineup/ek_wagon/design/img/img_bodycolor_01_11.jpg',
            'catalogue-v110-mitsubishi-xpander-hev' => 'https://www.mitsubishi-motors.co.th/content/dam/mitsubishi-motors-th/images/site-images/emotion/emotion-xpanderhev-gallery-1.jpg',
            'catalogue-v110-mitsubishi-xpander-cross-hev' => 'https://www.mitsubishi-motors.co.th/content/dam/mitsubishi-motors-th/images/cars/xpander-cross-hev/2024/primary/exterior/24xpander-cross-hev-01/W85_225_24XPANDER-CROSS-HEV-01-Rear-R.png?auto=webp&quality=70&width=2160',
            'catalogue-v110-mitsubishi-xforce-hev' => 'https://www.mitsubishi-motors.com/en/newsroom/newsrelease/2025/img/20250407_1_02.jpg',
            'catalogue-v110-mitsubishi-asx-hybrid-phev' => 'https://www.mitsubishi-motors.no/content/dam/mitsubishi-motors-no/images/cars/asx/2023/models/vsep6asl6a-eu-n48/primary/exterior/KNG/KNG_0_23ASX_E48.png?auto=webp&quality=70&width=640',
            'catalogue-v110-mercedes-benz-mercedes-amg-c-63-s-e-performance' => 'https://media.oneweb.mercedes-benz.com/images/static/v1/19661/1/5f/031299268007caabfdefc12a72958c4d8e0f8.jpg',
            'catalogue-v110-mercedes-benz-c-300-e' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/europe/PL/206056/805/iris.png?BKGND=9&IMGT=P27&POV=BE230%2CPZM&cp=UYiGBM9Qfw0VG3jbnu1OMA&im=Trim%2Cfuzz%3D60%2Cpadding%3D120&q=COSY-EU-100-1713d0VXqNWFqtyO67PobzIr3eWsrrCsdRRzwQZg9pZbMw3SGtlaWtsd2HVcUfpOkXGEubSJ0l3IrOB2NS1bApjI9I5uVrJQC31MjkzNwzwm7jZ73hKVFsM%25vq9UdyLRDO6Yax7NxrH1eJdn8wsOcoiZUidM4FGTlTg906V6PDMSoSeWzy3tsd7oTcUfKAqXGE4yRJ0lgIVOB2PMObApedlI5uscIQC3UkrkzNGmbm7j0hShKVBHM%25vqA8qyLR5i2YaxCtqrH1gObnMr%25E2fhdI5uefuQC3PS9kzN5P6m7jCGohKVzXW%25vqE32yLRaGmYaxH0ErH18Bin8wizwoiCpBxySfD2Mpg1oQ3IZbMFwRPRYEY7fHizWKfAN59S%25B8O&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-c-300-de' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/europe/PT/206008/806/iris.png?BKGND=9&IMGT=P27&POV=BE200%2CPZM&cp=UYiGBM9Qfw0VG3jbnu1OMA&im=Trim%2Cfuzz%3D60%2Cpadding%3D120&q=COSY-EU-100-1713d0VXqNWFqtyO67PobzIr3eWsrrCsdRRzwQZUkRZbMw3SGtlajtsd2YTcUfpX1XGEjy2J0lV0VOB2q8%25bApRiwI5ux6YQC30E3kzNHTwm7j86ohKViKw%25vq4UjyLRgAVYaxPrWrH1enun8wYzyoiZBkNM4FAm2Tg95Yn6PDaksSeWHXutsd8ZDcUfiMcXGE45mJ0lgCZOB2PyEbAp7oMIkbX1ZxcgTg9vh26PDK%25jSeWgKhtsdPHQcUferqXGEs9RJ0lwe1OB2A8cbAp5iCI5uCeuQCPFi2J%25xVZkF7pQ6ZH3MkNulKlO1OsxACeqUx4Wgj%25Xi5o&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-c-200-eq-boost' => 'https://1.bp.blogspot.com/-A0OUvh14yHw/YXkUns1mJgI/AAAAAAAAtGQ/IMXl3k1vO14euwGBeCtlMKGOYwwlUxysQCLcBGAsYHQ/s1536/W206%2BC200_1.jpg',
            'catalogue-v110-mercedes-benz-c-180-eq-boost' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/europe/DE/206041/806_056/iris.png?BKGND=9&IMGT=P27&POV=BE030%2CPZM&Resize=%28470%2C246%29&cp=UYiGBM9Qfw0VG3jbnu1OMA&q=COSY-EU-100-1713d0VXqNWFqtyO67PobzIr3eWsrrCsdRRzwQZg9pZbMw3SGtGyUtsd2HdcUfp8qXGEubmJ0l3ItOB2NMcbApjdRI5uVcZQC3qkWkzNwTVm7jZ7ZhKVFsM%25vq9UdyLRDO6Yax7oYrH1Kj1n8wsOfoiZUbXM4FG4fTg90zZ6PDBLFSeWAtutsd5cxcUfS86XGEvnwJ0lLoqOB2aScbApenCI5usmxQC3USQkzNGlum7j0OmhKVBHM%25vqA8qyLR5i2YaxCtqrH1gObnMr%25E2fKtI5ue6xQC3PS9kzN5P6m7jCGohKVzXW%25vqE32yLRaGmYaxH08rH18BOn8wizwoiZ46pM4zuA1YtEWpTuPwMkDEFIT9ZxexrlrKE847dvE5jCDtyAib&uni=m',
            'catalogue-v110-mercedes-benz-e-350-e' => 'https://www.mercedesbenzcertified.com/stocks/image_exterior_1_250129101714.jpg',
            'catalogue-v110-mercedes-benz-e-300-e' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/asia/MY/214054/805/iris.png?BKGND=9&IMGT=P27&POV=BE230%2CPZM&cp=lNXAeHSGoQ5P9nFlhA8PTA&im=Trim%2Cfuzz%3D.5%2Cpadding%3D120&q=COSY-EU-100-1713d0VXq0hFqtyO67PobzIr3eWsrrCsdRRzwQZ6TfZbMw3SGtxKFtsd2s%25cUfp8cXGEu9BJ0l36xOB2NzObApj7pI5ux5IQC31CWkzNwP9m7jA6ohKV5Kh%25vqCJTyLRgc6YaxPa9rH1eMdn8wsTnoiZB5lM4FAmMTg95Yn6PDakNSeWHeutsd8vNcUfi8jXGE49jJ0lgDfOB2Pb%25bApeIoI5uKMTQmIJwF1qH6PDK%25jSeWgKhtsdR4vcUfGUyXGE0aRJ0lBHAOB2A8nbAp5PpI5gZ8lXhRjwQZzOBT98uoQ3pE7EJxJeRB5PVsRiD4Nhc8An&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-e-300-de' => 'https://img.carmine.pt/_cmpro_/72287dbc-f9ac-4b13-a1a8-8079d0527c35/7451825f-35b9-4eda-a1f9-ee75c5d940d1.jpg?202604021139340000=&ar=4%3A3&q=75&w=1200',
            'catalogue-v110-mercedes-benz-e-200-e-300-eq-boost' => 'https://static.wixstatic.com/media/19f4e1_b5693679bff640c6a6ac59e3eda39ce6~mv2.png/v1/fill/w_1240%2Ch_766%2Cal_c/Mercedes%20E200%20Saloon%20Exclusive%20Mild%20Hybrid%20HK_.png',
            'catalogue-v110-mercedes-benz-e-300-bluetec-hybrid' => 'https://s1.cdn.autoevolution.com/images/gallery/MERCEDES-BENZ-E-Klasse--W212--3835_61.jpg',
            'catalogue-v110-mercedes-benz-s-400-hybrid' => 'https://group.mercedes-benz.com/bilder/unternehmen/tradition/geschichte/rezession-und-comeback/s-400-hybrid-w768xh768-cutout.jpg',
            'catalogue-v110-mercedes-benz-s-580-e' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/asia/MY/223168/805_055/iris.png?BKGND=9&IMGT=P27&POV=BE230%2CPZM&cp=lNXAeHSGoQ5P9nFlhA8PTA&im=Trim%2Cfuzz%3D.5%2Cpadding%3D120&q=COSY-EU-100-1713d0VXqrWeqtyO67PobzIr3eWsrrCsdRRzwQZ6ZHZbMw3SGtGyjtsd2spcUfpLcXGEuiXJ0l34AOB2NQnbApjtwI5uVczQC3qkOkzNRLkm7jxafhKVFSQ%25vq9tayLRDL2YaxW0SrH1dB%25n8wfA2oiZEkXM4FaIJTg9HQn6PD8ksSeWieStsd4H%25cUfgO1XGEPnmJ0leotOB2sMObApvnyI5uL5qQC3ah1kzNH%25km7j82ohKVipQ%25vq4ykyLRgY2YaxU0drH1Gmfn8w0P3oiZKbpM4Fv4lTg9Lh96PDa1FSeWHn9tsd8cQcUfiX6XGE4JYJ0lgChOB2Pz5bApe7lI5ulmJQC3vT6khQOZ9wR8SeWamMtsdLvNcUfeLkXGEsnXJ0lwohOB2ArFbAp5AXI5uC5MQC3zgFkzN7U9m7sDgubYwR9hDvT9vaij6hVNpLpIZIGwC7Ux0wPfeqYOgzT&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-s-500-e-s-560-e' => 'https://www.elbilgrossisten.no/cdn/shop/products/Ladeguiden-Mercedes-BenzS500PHEVhybrid-Profile-Elbilgrossisten_1200x1200.jpg?v=1629914203',
            'catalogue-v110-mercedes-benz-s-450-s-500-eq-boost' => 'https://hanoimercedes.vn/uploads/2177/images/Xanh-Emerald.jpg',
            'catalogue-v110-mercedes-benz-a-250-e' => 'https://www.mercedes-benz.de/content/dam/hq/passengercars/cars/a-class/hatchback-w177-fl-pi/overview/spa/05-2025/images/mercedes-benz-a-class-w177-spa-pih-2400x2400-05-2025.jpg',
            'catalogue-v110-mercedes-benz-b-250-e' => 'https://electricmobility.store/wp-content/uploads/2021/01/mercedes-benz-b250e-sports-tourer3.jpeg',
            'catalogue-v110-mercedes-benz-cla-250-e' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/europe/PL/118385/806_056/iris.png?BKGND=9&IMGT=P27&POV=BE270%2CPZM&cp=UYiGBM9Qfw0VG3jbnu1OMA&im=Trim%2Cfuzz%3D60%2Cpadding%3D100&q=COSY-EU-100-1713d0VXqNEFqtyO67PobzIr3eWsrrCsdRRzwQZg9pZbMw3SGtGyUtsd2HdcUfp8qXGEubXJ0l3otOB2NMEbApjI9I5ux5xQC31SrkzNBTwm7jAc3hKViSF%25vq4tTyLRgcRYaxPadrH1eHDn8wsocoiZUIEM4FnCwTg9otn6PDMSoSeWza3tsd7oTcUfKmcXGE4yBJ0lgIVOB2PMObApetbI5uscDQC3UkTkzNGLdm7j0M3hKV3XF%25vq8cXyr%25kWfDQ9J0l8o1OB2GM1bApFj5I5uC5MQC3zgOkzN7PKm7jKe3hKVvYW%25vqLJqyLRaOXYaxHNhrHAlGqhTWFEnlAyK71L1JnwxV4V%25d%25CWLH59zW0uBZTmGaX&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-gla-250-e' => 'https://paultan.org/cn/image/2023/03/23C0097_003-1.jpg',
            'catalogue-v110-mercedes-benz-glb-200-250-48v' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/europe/DE/247687/806_056/iris.png?BKGND=9&IMGT=P27&POV=BE230%2CPZM&cp=UYiGBM9Qfw0VG3jbnu1OMA&im=Trim%2Cfuzz%3D.5%2Cpadding%3D120&q=COSY-EU-100-1713d0VXqNEFqtyO67PobzIr3eWsrrCsdRRzwQZYZ4ZbMw3SGtGyUtsd2HdcUfp8qXGEuiYJ0l3IJOB2NMcbApj7bI5ux5xQC31SrkzNBTwm7jA7mhKV5Yh%25vqCJjyLRgcDYaxPXSrH1eHrn8ws8WoiZUMgM4FnCwTg9otn6PDC7NSeWza3tsd7oTcUfKmfXGE4yBJ0lgIVOB2PMObApetbI5uscDQC3UkTkzNGLwm7j0afhKVBTN%25vqNrkyLRjODYax4JOroYhfld7SbApPdXI5ug6FQC3D0RkzN7Pbm7jKevhKVvsN%25vqLJcyLRa3mYaBEUVmMDZfrE820VxHxXr1RjijhWh5DvaAFCDGp0wMkULc&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-mercedes-amg-gt-63-s-e-performance' => 'https://www.mercedes-benz.de/content/dam/hq/passengercars/cars/amg-gt/amg-gt-4-door-coupe-e-performance-x290-pi/overview/equipment/02-2025/images/mercedes-amg-gt-63-se-perf-x290-equipment-exterior-night-package-ii-764x573-02-2025.jpg/1747938422839.jpg?im=Crop%2Crect%3D%280%2C0%2C764%2C573%29%3BResize%3D%282730%2C2048%29',
            'catalogue-v110-mercedes-benz-glc-350-e' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/asia/AU/254656/806_056/iris.png?BKGND=9&IMGT=P27&POV=BE180%2CPZM&cp=lNXAeHSGoQ5P9nFlhA8PTA&im=Trim%2Cfuzz%3D.5%2Cpadding%3D120&q=COSY-EU-100-1713d0VXqaWFqtyO67PobzIr3eWsrrCsdRRzwQZUnRZbMw3SGtGyUtsd1HpcUfwOuXGEZ93J0lF6NOB2NrFbApjPpI5uVeZQC3qhrkzNR%25bm7jxymhKV1XQ%25vqwJjyLRZQfYaxFrprH19grn8wDPboiZEb9M4Fl4lTg92zO6PDp7NSeWuXjtsd3YQcUfNrqXGEj9RJ0lVDZOB2qbcbApUAbI5uG4YQC30h3kzNBlNm7jAhZhKV5%25W%25vq4t9yLRgc6YaxPaYrH1eHDn8wsAxoiZU7NM4FGKwTg90626PDBSqSeWAhhtsd6hDcUfS8kXGEvyVJ0lLHJOB2a81bApHixI5u86DQC3iX7kzN4LZm7jgMIhKVBSL%25vqAUDyLR5AWYaxCoWrH1zMin8w7VxoiZK%25oM4FvyPTg9L6k6PDaGpSeWH0Itsd8BtcUfiAfXGE45BJ0lgCZOB2frqbApEnyI5ulmJQC32TQkzNLtnm7jacmhKVHXt%25vq8cXyr%25kWfDr3J0l4YfOB2igSbAp0ApI5uB5ZQC3AhWkzN5%256m7jCSwhKVz89%25vqEtTyLRlL2YaxHXSrH18Hdn8wi8yoiZ45YM4FgKFTg9Pv36PDecpSeWsXjtsLV79MOuljcVGfP9R%25dhcfWF0FT3THuPsa28uKxvEOo7em&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-glc-300-e' => 'https://vanmossel.images.ilsa.cloud/a6f86685-051f-451b-80a1-06a4a7ef8b61.jpg',
            'catalogue-v110-mercedes-benz-glc-200-300-48v' => 'https://media.oneweb.mercedes-benz.com/images/dynamic/europe/IT/254651/806/iris.png?BKGND=9&IMGT=P27&POV=BE020%2CPZM&cp=UYiGBM9Qfw0VG3jbnu1OMA&q=COSY-EU-100-1713d0VXqaWFqtyO67PobzIr3eWsrrCsdRRzwQZUnRZbMw3SGtxX2tsd1ZpcUfwMuXGEuywJ0l30fOB2Ng1bApjkbI5uV6JQC3qSpkzNRmdm7jxMohKVFSR%25vq9v9yLRDG6YaxW0ErH1dMln8wfQcoiZEkpM4FlRuTg92xV6PDpSoSeWiKItsd4ZdcUfgMqXGEzyjJ0l7YlOB2KBjbApvilI5uLeDQC3aspkzNHmwm7j8hfhKVkSN%25vqmBkyLRhQ2YaxUXdrH1GBrn8w0A2oiZBklM4FATcTg95U96PDCGuSeWzQMtsd8hGcUfiONXGE49dJ0lg6JOB2PS5bApe7RI5usKYQC3UvQkzNGLdm7j0M3hKV3SF%25vqNtTyLRjnmYax4XSrH1gItn8wPQboiZeIQM6oY2ulVzkzNUtwm7jsGOhKVzKV%25vq7vdyLRKnRYaxvoJrH1Ljln8wabdoiZHs2M4FNIJTg9PQO6PDePpSeWseMtsdUvTcUfG8qXGHRvW6INpqJRBlsW1NEyJlfDADSjSiNsG8u4NLwa2ITvU%25&uni=m&width=1920',
            'catalogue-v110-mercedes-benz-gle-350-de' => 'https://media.oneweb.mercedes-benz.com/images/static/v1/22226/6/02/f32d697834868bf2b5d769a910d667d8021d9.jpg',
            'catalogue-v110-mercedes-benz-gle-400-e' => 'https://www.mercedes-benz.com.my/content/dam/malaysia/passengercars/campaign/agility-campaign/GLE.jpg/1776172571990.jpg?im=Crop%2Crect%3D%280%2C0%2C800%2C800%29%3BResize%3D%28800%2C800%29',
            'catalogue-v110-mercedes-benz-gle-450-eq-boost' => 'https://www.mercedes-benz.ca/content/dam/mb-nafta/ca/myco/my21/gle/suv/byo-options/MBCAN-2021-GLE-SUV-MP-015.jpg',
            'catalogue-v110-mercedes-benz-gls-450-eq-boost' => 'https://www.mercedes-benz.ca/content/dam/mb-nafta/ca/myco/my22/gls/suv/byo-options/MBCAN-2022-GLS-SUV-MP-005.jpg',
            'catalogue-v110-mercedes-benz-mercedes-maybach-s-580' => 'https://x0.ifengimg.com/res/2023/1EA2761E5F57FA57ECDB6CA3F037553FAF2144B4_size778_w2800_h2100.jpg',
            'catalogue-v110-mercedes-benz-cle-300-e-450-48v' => 'https://img.sm360.ca/ir/w640h390c/images/newcar/ca/2025/mercedes-benz/cle-coupe/450-4matic/coupe/exteriorColors/2025_mercedes-benz_cle-coupe_450-4matic_ext_032_149.png',
            'catalogue-v110-bmw-activehybrid-3' => 'https://mediapool.bmwgroup.com/cache/P9/201109/P90083154/P90083154-bmw-activehybrid-3-10-2011-2422px.jpg',
            'catalogue-v110-bmw-activehybrid-5' => 'https://mediapool.bmwgroup.com/cache/P9/201108/P90081504/P90081504-bmw-activehybrid-5-09-2011-2099px.jpg',
            'catalogue-v110-bmw-activehybrid-7' => 'https://mediapool.bmwgroup.com/cache/P9/201205/P90094296/P90094296-the-new-bmw-activehybrid-7-05-2012-2000px.jpg',
            'catalogue-v110-bmw-225xe-active-tourer' => 'https://mediapool.bmwgroup.com/cache/P9/201712/P90288861/P90288861-the-new-bmw-225xe-iperformance-01-2018-2249px.jpg',
            'catalogue-v110-bmw-530e' => 'https://mediapool.bmwgroup.com/download/edown/pressclub/publicq?actEvent=image&attachment=1&dokNo=P90422463',
            'catalogue-v110-bmw-740e-740le' => 'https://mediapool.bmwgroup.com/cache/P9/201604/P90215606/P90215606-bmw-individual-740le-iperformance-the-next-100-years-exterior-04-2016-1986px.jpg',
            'catalogue-v110-bmw-745e-745le' => 'https://mediapool.bmwgroup.com/cache/P9/201901/P90335798/P90335798-the-new-bmw-745e-02-2019-2000px.jpg',
            'catalogue-v110-bmw-i8-i8-roadster' => 'https://bmw.autoua.net/media/uploads/bmw/i8_roadster/i8_roadster.jpg',
            'catalogue-v110-bmw-x5-xdrive40e' => 'https://mediapool.bmwgroup.com/cache/P9/201503/P90175318/P90175318-the-new-bmw-x5-xdrive40e-2250px.jpg',
            'catalogue-v110-bmw-x5-xdrive45e-50e' => 'https://mediapool.bmwgroup.com/cache/P9/201808/P90320131/P90320131-the-new-bmw-x5-xdrive45e-iperformance-09-2018-2000px.jpg',
            'catalogue-v110-bmw-x1-xdrive25e' => 'https://imgs.inventario.pro/coche/610/3509312/8eb5cd57-5162-4122-9289-b6b8f5e03e01_original.jpg',
            'catalogue-v110-bmw-520i-48v-versions' => 'https://paultan.org/cn/image/2024/09/BMW-520i-M-Sport-Mineral-White-Veganza-Brown-3.jpg',
            'catalogue-v110-bmw-x5-xdrive40i-48v' => 'https://www.haroldzeiglerbmw.com/assets/stock/colormatched_01/transparent/1280/cc_2026bms19_01_1280/cc_2026bms191998059_01_1280_a96.png?bg-color=FFFFFF&timestamp=0001-01-01T00%3A00%3A00&width=800+800w',
            'catalogue-v110-bmw-x7-xdrive40i-48v' => 'https://uudai-bmw.vn/storage/page-dong-xe/anh-bia-moi/396-bmw-x7-xdrive40i-pureexcellence-lci-alpine-white-coffee-black-1.webp',
            'catalogue-v110-bmw-x3-xdrive30e' => 'https://www.bmw.si/sl/all-models/x-serija/x3/bmw-x3-phev/_jcr_content/root/maincontent/container/contentblueprint/contentblueprint/contentblueprint/image.coreimg.png/1770203393224/bmw-x-series-x3-30e-xdrive.png',
            'catalogue-v110-bmw-x2-xdrive25e' => 'https://www.bmw.kg/content/dam/bmw/common/topics/fascination-bmw/electromobility2020-new/phev-new/bmw-emobility-phev-model-carousel-08.jpg.asset.1633518006892.jpg',
            'catalogue-v110-bmw-545e' => 'https://mediapool.bmwgroup.com/cache/P9/202008/P90395447/P90395447-the-new-bmw-545e-xdrive-sedan-08-2020-2250px.jpg',
            'catalogue-v110-bmw-550e' => 'https://carimages.d2cmedia.ca/newcarimages/en/cb683c2e61bbbd0/BMW/5%20Series%20PHEV/2026/2100/471076/White/front45.png',
            'catalogue-v110-bmw-750e' => 'https://paultan.org/cn/image/2023/05/3.-The-New-BMW-750e-xDrive-Pure-Excellence-Mineral-White.png',
            'catalogue-v110-bmw-230e-active-tourer' => 'https://mediapool.bmwgroup.com/cache/P9/202109/P90437847/P90437847-the-all-new-bmw-230e-xdrive-active-tourer-10-2021-2250px.jpg',
            'catalogue-v110-bmw-bmw-xm' => 'https://paultan.org/cn/image/2022/09/2023-BMW-XM-debut-133.jpg',
            'catalogue-v110-bmw-bmw-m5-2025' => 'https://imgauto-phinf.pstatic.net/20241210_118/auto_1733792549338TYgpJ_PNG/20241210100214_WPdRs0Yn.png',
        ];
        $replaceLegacyUrls = [
            'https://www.tochigitoyota.com/file/cms/-1/9496/zvw52-ahxgb_0_30.png',
            'https://global.toyota/pages/news/images/2026/09/04/1330/20260904_01_ogp.jpg',
            'https://fastly.clutch.ca/stc-photos/27ae2a6d-1808-488f-b45f-0bb0a366f1d3.png?class=small'
        ];

        DB::transaction(function () use ($photos, $replaceLegacyUrls): void {
            foreach ($photos as $slug => $url) {
                $row = DB::table('ccs_website_content')
                    ->where('kind', 'models')
                    ->where('slug', $slug)
                    ->first(['id', 'data', 'revision']);

                if (!$row) {
                    continue;
                }

                $data = json_decode($row->data, true, 512, JSON_THROW_ON_ERROR);
                $changed = false;
                foreach (['image', 'full_image'] as $field) {
                    $current = trim((string) ($data[$field] ?? ''));
                    if ($current === '' || in_array($current, $replaceLegacyUrls, true)) {
                        if (($data[$field] ?? null) !== $url) {
                            $data[$field] = $url;
                            $changed = true;
                        }
                    }
                }

                if (!$changed) {
                    continue;
                }

                $updated = DB::table('ccs_website_content')
                    ->where('id', $row->id)
                    ->where('revision', $row->revision)
                    ->update([
                        'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'revision' => (int) $row->revision + 1,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException("Could not safely update catalogue photos for {$slug}.");
                }
            }
        });
    }

    public function down(): void
    {
        // Keep the photos and dashboard edits in place on rollback.
    }
};
