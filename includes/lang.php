<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$langues_valides = ['fr', 'ar'];

if (isset($_GET['lang']) && in_array($_GET['lang'], $langues_valides, true)) {
    $_SESSION['lang'] = $_GET['lang'];
}

$lang = $_SESSION['lang'] ?? 'fr';
$dir  = ($lang === 'ar') ? 'rtl' : 'ltr';

$textes = [
    'fr' => [
        'tagline'        => 'Le bon choix à chaque fois.',
        'nav_home'       => 'Accueil',
        'nav_ads'        => 'Annonces',
        'nav_contact'    => 'Contact',
        'nav_about'      => 'À propos',
        'publish'        => 'Publier une annonce',
        'login'          => 'Connexion',
        'search_ph'      => 'Que cherchez-vous ?',
        'search_btn'     => 'Rechercher',
        'hero_title'     => 'Achetez et vendez partout au Maroc',
        'hero_sub'       => 'Des milliers de petites annonces, simples et sécurisées.',
        'cat_title'      => 'Catégories',
        'featured_title' => 'Annonces à la une',
        'latest_title'   => 'Dernières annonces',
        'empty_ads'      => 'Aucune annonce pour le moment.',
        'how_title'      => 'Comment ça marche ?',
        'step1_t'        => 'Créez un compte',
        'step1_p'        => 'Inscrivez-vous gratuitement en quelques secondes.',
        'step2_t'        => 'Publiez votre annonce',
        'step2_p'        => 'Ajoutez vos photos, votre prix et votre ville.',
        'step3_t'        => 'Soyez contacté',
        'step3_p'        => 'Après validation, les acheteurs vous contactent.',
        'cta_title'      => 'Vous avez quelque chose à vendre ?',
        'cta_text'       => 'Publiez votre annonce en quelques minutes.',
        'footer_about'   => 'Acheto, la plateforme marocaine de petites annonces.',
        'footer_links'   => 'Liens utiles',
        'terms'          => 'Conditions d\'utilisation',
        'privacy'        => 'Politique de confidentialité',
        'rights'         => 'Tous droits réservés.',
        'cat_1'  => 'Véhicules',
        'cat_2'  => 'Immobilier',
        'cat_3'  => 'Téléphones et tablettes',
        'cat_4'  => 'Informatique et électronique',
        'cat_5'  => 'Maison et meubles',
        'cat_6'  => 'Vêtements et accessoires',
        'cat_7'  => 'Emploi et services',
        'cat_8'  => 'Loisirs et sports',
        'cat_9'  => 'Animaux',
        'cat_10' => 'Matériel professionnel',
        'cat_11' => 'Autres',
    ],
    'ar' => [
        'tagline'        => 'الاختيار الصحيح في كل مرة.',
        'nav_home'       => 'الرئيسية',
        'nav_ads'        => 'الإعلانات',
        'nav_contact'    => 'اتصل بنا',
        'nav_about'      => 'من نحن',
        'publish'        => 'انشر إعلانا',
        'login'          => 'تسجيل الدخول',
        'search_ph'      => 'عن ماذا تبحث؟',
        'search_btn'     => 'بحث',
        'hero_title'     => 'اشترِ وبع في كل أنحاء المغرب',
        'hero_sub'       => 'آلاف الإعلانات المبوبة، بسيطة وآمنة.',
        'cat_title'      => 'الفئات',
        'featured_title' => 'إعلانات مميزة',
        'latest_title'   => 'آخر الإعلانات',
        'empty_ads'      => 'لا توجد إعلانات حاليا.',
        'how_title'      => 'كيف يعمل الموقع؟',
        'step1_t'        => 'أنشئ حسابا',
        'step1_p'        => 'سجّل مجانا في ثوان قليلة.',
        'step2_t'        => 'انشر إعلانك',
        'step2_p'        => 'أضف صورك وسعرك ومدينتك.',
        'step3_t'        => 'سيتواصل معك المشترون',
        'step3_p'        => 'بعد المراجعة، يتصل بك المشترون.',
        'cta_title'      => 'هل لديك شيء للبيع؟',
        'cta_text'       => 'انشر إعلانك في دقائق قليلة.',
        'footer_about'   => 'أشيتو، منصة مغربية للإعلانات المبوبة.',
        'footer_links'   => 'روابط مفيدة',
        'terms'          => 'شروط الاستخدام',
        'privacy'        => 'سياسة الخصوصية',
        'rights'         => 'جميع الحقوق محفوظة.',
        'cat_1'  => 'السيارات',
        'cat_2'  => 'العقارات',
        'cat_3'  => 'الهواتف واللوحات',
        'cat_4'  => 'الإعلاميات والإلكترونيات',
        'cat_5'  => 'المنزل والأثاث',
        'cat_6'  => 'الملابس والإكسسوارات',
        'cat_7'  => 'الوظائف والخدمات',
        'cat_8'  => 'الترفيه والرياضة',
        'cat_9'  => 'الحيوانات',
        'cat_10' => 'المعدات المهنية',
        'cat_11' => 'أخرى',
    ],
];

function t(string $cle): string
{
    global $textes, $lang;
    return htmlspecialchars($textes[$lang][$cle] ?? $cle, ENT_QUOTES, 'UTF-8');
}