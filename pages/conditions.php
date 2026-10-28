<?php
require_once __DIR__ . '/../includes/lang.php';

$contenu = [
    'fr' => [
        'title' => 'Conditions d\'utilisation',
        'intro' => 'En utilisant Acheto, vous acceptez les conditions ci-dessous.',
        'sections' => [
            ['Objet', 'Acheto est une plateforme marocaine de petites annonces. Elle permet de publier, rechercher et consulter des annonces de vente de produits et de services. Acheto n\'intervient pas dans les transactions entre utilisateurs.'],
            ['Compte utilisateur', 'Pour publier une annonce, vous devez créer un compte avec des informations exactes. Vous êtes responsable de la confidentialité de votre mot de passe et de toutes les actions effectuées depuis votre compte.'],
            ['Publication d\'annonces', 'Chaque annonce doit décrire honnêtement le produit ou le service proposé, avec un prix en dirhams (DH) et des photos réelles. Il est interdit de publier des contenus illégaux, trompeurs, offensants ou qui portent atteinte aux droits d\'autrui.'],
            ['Validation et durée', 'Toute annonce est examinée par un administrateur avant sa publication. Elle peut être refusée avec un motif. Une annonce approuvée reste visible pendant 60 jours, puis elle expire automatiquement.'],
            ['Responsabilité', 'Les utilisateurs sont seuls responsables du contenu de leurs annonces et des échanges avec les autres utilisateurs. Nous vous conseillons de vérifier le produit et de rencontrer le vendeur dans un lieu public avant tout paiement.'],
            ['Suppression et suspension', 'Acheto peut supprimer une annonce ou suspendre un compte en cas de non-respect de ces conditions. Vous pouvez supprimer vos annonces à tout moment depuis la page « Mes annonces ».'],
            ['Contact', 'Pour toute question, utilisez la page Contact du site.'],
        ],
    ],
    'ar' => [
        'title' => 'شروط الاستخدام',
        'intro' => 'باستعمالك لموقع أشيتو، فإنك توافق على الشروط التالية.',
        'sections' => [
            ['الغرض', 'أشيتو منصة مغربية للإعلانات المبوبة، تتيح نشر إعلانات بيع المنتجات والخدمات والبحث عنها والاطلاع عليها. لا يتدخل أشيتو في المعاملات بين المستخدمين.'],
            ['حساب المستخدم', 'لنشر إعلان، يجب إنشاء حساب بمعلومات صحيحة. أنت مسؤول عن سرية كلمة المرور وعن جميع الإجراءات المنجزة من حسابك.'],
            ['نشر الإعلانات', 'يجب أن يصف كل إعلان المنتج أو الخدمة بصدق، مع ثمن بالدرهم وصور حقيقية. يمنع نشر محتوى غير قانوني أو مضلل أو مسيء أو ينتهك حقوق الغير.'],
            ['المراجعة والمدة', 'تتم مراجعة كل إعلان من طرف مشرف قبل نشره، وقد يُرفض مع ذكر السبب. يبقى الإعلان المقبول ظاهرا لمدة 60 يوما ثم ينتهي تلقائيا.'],
            ['المسؤولية', 'المستخدمون وحدهم مسؤولون عن محتوى إعلاناتهم وعن تواصلهم مع المستخدمين الآخرين. ننصحك بمعاينة المنتج ولقاء البائع في مكان عمومي قبل أي أداء.'],
            ['الحذف والإيقاف', 'يمكن لأشيتو حذف إعلان أو إيقاف حساب عند مخالفة هذه الشروط. يمكنك حذف إعلاناتك في أي وقت من صفحة «إعلاناتي».'],
            ['التواصل', 'لأي سؤال، استعمل صفحة «اتصل بنا» في الموقع.'],
        ],
    ],
][$lang];

$page_title = $contenu['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <h1 class="h3 fw-bold mb-3"><?= e($contenu['title']) ?></h1>
            <p class="text-muted-ac mb-4"><?= e($contenu['intro']) ?></p>

            <?php foreach ($contenu['sections'] as $i => [$titre, $texte]): ?>
                <section class="mb-4">
                    <h2 class="h5 fw-bold"><?= ($i + 1) ?>. <?= e($titre) ?></h2>
                    <p class="mb-0"><?= e($texte) ?></p>
                </section>
            <?php endforeach; ?>

        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>