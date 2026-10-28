<?php
require_once __DIR__ . '/../includes/lang.php';

$contenu = [
    'fr' => [
        'title'    => 'À propos d\'Acheto',
        'intro'    => 'Acheto est une plateforme marocaine de petites annonces qui réunit acheteurs et vendeurs partout au Maroc.',
        'mission_t'=> 'Notre mission',
        'mission'  => 'Rendre l\'achat et la vente simples, rapides et sûrs : publier une annonce en quelques minutes, trouver ce que l\'on cherche grâce à la recherche et aux filtres, et contacter directement le vendeur.',
        'how_t'    => 'Comment ça marche ?',
        'steps'    => [
            ['Créez un compte', 'Inscrivez-vous gratuitement en quelques secondes.'],
            ['Publiez votre annonce', 'Ajoutez vos photos, votre prix et votre ville, puis envoyez-la pour validation.'],
            ['Soyez contacté', 'Après validation, votre annonce est visible pendant 60 jours et les acheteurs vous contactent.'],
        ],
        'values_t' => 'Nos engagements',
        'values'   => [
            ['Annonces vérifiées', 'Chaque annonce est examinée par un administrateur avant d\'être publiée.'],
            ['Simplicité', 'Une interface claire, disponible en arabe et en français, sur ordinateur, tablette et téléphone.'],
            ['Sécurité', 'Mots de passe protégés, formulaires sécurisés et photos contrôlées.'],
        ],
        'browse'   => 'Parcourir les annonces',
        'publish'  => 'Publier une annonce',
    ],
    'ar' => [
        'title'    => 'عن أشيتو',
        'intro'    => 'أشيتو منصة مغربية للإعلانات المبوبة تجمع المشترين والبائعين في كل أنحاء المغرب.',
        'mission_t'=> 'مهمتنا',
        'mission'  => 'جعل البيع والشراء بسيطا وسريعا وآمنا: نشر إعلان في دقائق، والعثور على ما تبحث عنه بفضل البحث والفلاتر، والتواصل مباشرة مع البائع.',
        'how_t'    => 'كيف يعمل الموقع؟',
        'steps'    => [
            ['أنشئ حسابا', 'سجّل مجانا في ثوان قليلة.'],
            ['انشر إعلانك', 'أضف صورك وثمنك ومدينتك، ثم أرسله للمراجعة.'],
            ['سيتواصل معك المشترون', 'بعد المراجعة يظهر إعلانك لمدة 60 يوما ويتصل بك المشترون.'],
        ],
        'values_t' => 'التزاماتنا',
        'values'   => [
            ['إعلانات مراجَعة', 'يراجع مشرف كل إعلان قبل نشره.'],
            ['البساطة', 'واجهة واضحة بالعربية والفرنسية، على الحاسوب واللوحة والهاتف.'],
            ['الأمان', 'كلمات مرور محمية ونماذج آمنة وصور مراقَبة.'],
        ],
        'browse'   => 'تصفح الإعلانات',
        'publish'  => 'انشر إعلانا',
    ],
][$lang];

$page_title = $contenu['title'];
require_once __DIR__ . '/../includes/header.php';

$style_carte = 'background:var(--ac-surface); border:1px solid var(--ac-border); border-radius:var(--ac-radius); box-shadow:var(--ac-shadow);';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">

            <div class="text-center mb-5">
                <img src="<?= BASE_URL ?>assets/logo.jpeg" alt="Acheto" style="height:90px; width:auto;" class="mb-3">
                <h1 class="h2 fw-bold"><?= e($contenu['title']) ?></h1>
                <p class="text-muted-ac fs-5 mb-0"><?= e($contenu['intro']) ?></p>
            </div>

            <section class="p-4 mb-4" style="<?= $style_carte ?>">
                <h2 class="h4 fw-bold"><?= e($contenu['mission_t']) ?></h2>
                <p class="mb-0"><?= e($contenu['mission']) ?></p>
            </section>

            <h2 class="h4 fw-bold mb-3"><?= e($contenu['how_t']) ?></h2>
            <div class="row g-3 mb-4">
                <?php foreach ($contenu['steps'] as $i => [$titre, $texte]): ?>
                    <div class="col-12 col-md-4">
                        <div class="ac-step h-100" style="<?= $style_carte ?>">
                            <div class="ac-step-num"><?= $i + 1 ?></div>
                            <h3 class="h6 fw-bold"><?= e($titre) ?></h3>
                            <p class="text-muted-ac mb-0"><?= e($texte) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h2 class="h4 fw-bold mb-3"><?= e($contenu['values_t']) ?></h2>
            <div class="row g-3 mb-5">
                <?php foreach ($contenu['values'] as [$titre, $texte]): ?>
                    <div class="col-12 col-md-4">
                        <div class="p-3 h-100" style="<?= $style_carte ?>">
                            <h3 class="h6 fw-bold" style="color:var(--ac-blue);"><?= e($titre) ?></h3>
                            <p class="mb-0"><?= e($texte) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-ac-blue" href="<?= BASE_URL ?>pages/annonces.php"><?= e($contenu['browse']) ?></a>
                <a class="btn btn-ac-orange" href="<?= BASE_URL ?>pages/publier.php"><?= e($contenu['publish']) ?></a>
            </div>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>