<?php
// 1. Lấy tất cả danh mục Cha
$parents = $pdo->query("SELECT * FROM categories WHERE parent_id IS NULL")->fetchAll(PDO::FETCH_ASSOC);

echo '<div class="main-menu">';

foreach ($parents as $parent) {
    // Hiển thị tên Danh mục Cha
    echo '<div class="menu-column">';
    echo '<h3>' . htmlspecialchars($parent['name']) . '</h3>';
    
    // 2. Với mỗi Cha, tìm các con thuộc về nó
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
    $stmt->execute([$parent['id']]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Hiển thị danh sách Con
    echo '<ul>';
    foreach ($children as $child) {
        echo '<li><a href="category.php?id=' . $child['id'] . '">' . htmlspecialchars($child['name']) . '</a></li>';
    }
    echo '</ul>';
    echo '</div>';
}
echo '</div>';
?>