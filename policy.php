<?php 
include 'db.php'; 
include 'header.php'; 
?>

<link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
<?php
// Lấy loại chính sách từ URL, mặc định là 'loyalty' (KH thân thiết)
$type = $_GET['type'] ?? 'loyalty'; 

// Khởi tạo các biến chứa nội dung
$title = "";
$content = "";

// ĐIỀN NỘI DUNG CỦA BẠN VÀO ĐÂY
switch ($type) {
    case 'loyalty':
        $title = "CHÍNH SÁCH KHÁCH HÀNG THÂN THIẾT";
        $content = '
            <p>December luôn trân trọng sự yêu thương và ủng hộ của khách hàng. Vì vậy, chúng tôi mang đến chương trình khách hàng thân thiết với nhiều ưu đãi hấp dẫn dành cho những khách hàng thường xuyên mua bánh tại cửa hàng.</p>

            <p>December sẽ tự động tích lũy tổng doanh số mua hàng theo số điện thoại của khách hàng. Khi đạt các mức doanh số sau, khách hàng sẽ nhận được ưu đãi giảm giá cho những đơn hàng tiếp theo:</p>

            <ul class="list-unstyled">
                <li>- Tổng doanh số từ 500.000 VNĐ: Giảm 3%.</li>
                <li>- Tổng doanh số từ 1.000.000 VNĐ: Giảm 5%.</li>
                <li>- Tổng doanh số từ 2.000.000 VNĐ: Giảm 8%.</li>
                <li>- Tổng doanh số từ 3.000.000 VNĐ: Giảm 10%.</li>
            </ul>

            <p class="fw-bold text-dark mt-3">December – Càng thưởng thức, càng nhận nhiều ưu đãi!</p>

            <p class="mt-3">Cảm ơn bạn đã lựa chọn December. Chúng tôi hy vọng mỗi chiếc bánh đều mang đến cho bạn những khoảnh khắc ngọt ngào và đáng nhớ.</p>';
        break;

    case 'care':
        $title = "";
        $content = '
            <div class="care-section">
                <h5 class="fw-bold text-dark mb-3">CHÍNH SÁCH GIAO HÀNG</h5>
                <p>December hỗ trợ giao bánh trong phạm vi hoạt động của cửa hàng.</p>
                <ul class="list-unstyled">
                    <li>- Thời gian giao hàng được thông báo khi xác nhận đơn.</li>
                    <li>- Phí vận chuyển phụ thuộc vào khoảng cách giao hàng.</li>
                    <li>- Bánh được đóng gói cẩn thận để hạn chế va đập, biến dạng.</li>
                    <li>- Khách hàng vui lòng kiểm tra sản phẩm khi nhận hàng.</li>
                    <li>- Nếu bánh bị hư hỏng hoặc giao sai, vui lòng liên hệ December để được hỗ trợ.</li>
                </ul>
                <p class="fw-bold text-dark mt-3">December – Giao tận tay, trao trọn vị ngọt!</p>

                <h5 class="fw-bold text-dark mt-4 mb-3">CHÍNH SÁCH THANH TOÁN</h5>
                <p>December hỗ trợ hai hình thức thanh toán:</p>
                <ul class="list-unstyled">
                    <li>- <strong>Thanh toán COD:</strong> Khách hàng thanh toán tiền mặt khi nhận bánh.</li>
                    <li>- <strong>Chuyển khoản ngân hàng:</strong> Khách hàng thanh toán trước theo thông tin tài khoản của cửa hàng.</li>
                </ul>
                <p><strong>Lưu ý:</strong> Vui lòng kiểm tra kỹ thông tin và giá trị đơn hàng trước khi thanh toán. Đơn hàng chuyển khoản được xác nhận sau khi cửa hàng nhận được thanh toán.</p>

                <h5 class="fw-bold text-dark mt-4 mb-3">HƯỚNG DẪN ĐẶT HÀNG</h5>
                <ol class="ps-3 mb-4">
                    <li><strong>Chọn sản phẩm:</strong> Truy cập website và lựa chọn loại bánh yêu thích.</li>
                    <li><strong>Thêm giỏ hàng:</strong> Chọn số lượng và thêm sản phẩm vào giỏ hàng.</li>
                    <li><strong>Thanh toán:</strong> Kiểm tra đơn hàng, nhập thông tin nhận hàng và chọn phương thức thanh toán.</li>
                    <li><strong>Xác nhận:</strong> Hoàn tất đặt hàng và chờ December xác nhận.</li>
                    <li><strong>Nhận bánh:</strong> Nhận hàng, kiểm tra sản phẩm và thưởng thức.</li>
                </ol>

                <h5 class="fw-bold text-dark mt-4 mb-3">CÂU HỎI THƯỜNG GẶP</h5>
                <p><strong>1. December có những loại bánh nào?</strong><br>
                Cửa hàng cung cấp nhiều loại donut với hương vị đa dạng như socola, Oreo mochi, tiramisu, cốm sữa chua và nhiều loại bánh khác.</p>

                <p><strong>2. December có giao hàng không?</strong><br>
                Có. Cửa hàng hỗ trợ giao hàng trong phạm vi hoạt động.</p>

                <p><strong>3. Có nhận đặt bánh số lượng lớn không?</strong><br>
                Có. December nhận đặt bánh cho sinh nhật, tiệc và các sự kiện.</p>

                <p><strong>4. Bánh bảo quản được bao lâu?</strong><br>
                Thời hạn sử dụng tùy thuộc vào từng loại bánh. Khách hàng vui lòng xem hướng dẫn trên bao bì.</p>

                <p><strong>5. Nếu nhận bánh bị hỏng thì phải làm sao?</strong><br>
                Vui lòng chụp ảnh sản phẩm và liên hệ cửa hàng trong vòng 2 giờ kể từ khi nhận hàng.</p>

                <h5 class="fw-bold text-dark mt-4 mb-3">CHÍNH SÁCH ĐẶT BÁNH THEO YÊU CẦU</h5>
                <ul class="list-unstyled">
                    <li>- December nhận đặt bánh theo yêu cầu dành cho sinh nhật, tiệc liên hoan và các sự kiện.</li>
                    <li>- Khách hàng có thể đặt bánh với số lượng lớn và lựa chọn nhiều hương vị.</li>
                    <li>- Hỗ trợ đóng gói thành hộp quà tặng theo nhu cầu.</li>
                    <li>- Khách hàng vui lòng cung cấp loại bánh, số lượng và thời gian nhận hàng.</li>
                    <li>- Đơn hàng đặc biệt cần được đặt trước để cửa hàng chuẩn bị.</li>
                    <li>- Giá bán và thời gian hoàn thành sẽ được xác nhận trước khi đặt hàng.</li>
                </ul>
                <p class="fw-bold text-dark mt-3">December – Ngọt ngào trong từng khoảnh khắc!</p>
            </div>';
        break;

    case 'return':
        $title = "CHÍNH SÁCH ĐỔI VÀ TRẢ HÀNG";
        $content = '
            <p>December luôn mong muốn mang đến cho khách hàng những chiếc bánh thơm ngon và chất lượng nhất. Vì vậy, chúng tôi áp dụng chính sách đổi trả như sau:</p>

            <ul class="list-unstyled">
                <li>- <strong>Thời gian phản hồi:</strong> Khách hàng vui lòng kiểm tra sản phẩm ngay khi nhận hàng và liên hệ với December trong vòng 2 giờ kể từ thời điểm nhận bánh nếu phát hiện vấn đề.</li>
                <li>- <strong>Điều kiện đổi sản phẩm:</strong> Hỗ trợ đổi bánh nếu sản phẩm bị lỗi, hư hỏng trong quá trình vận chuyển, giao sai loại bánh hoặc không đúng số lượng đã đặt.</li>
                <li>- <strong>Tình trạng sản phẩm:</strong> Khách hàng vui lòng cung cấp hình ảnh hoặc video sản phẩm khi nhận hàng để December xác minh và hỗ trợ.</li>
                <li>- <strong>Trường hợp không hỗ trợ đổi trả:</strong> December không áp dụng đổi trả đối với các trường hợp khách hàng thay đổi sở thích, đặt nhầm hương vị hoặc sản phẩm đã được sử dụng và bảo quản không đúng hướng dẫn.</li>
                <li>- <strong>Chi phí đổi trả:</strong> Nếu lỗi xuất phát từ phía December, cửa hàng sẽ chịu toàn bộ chi phí vận chuyển và đổi sản phẩm cho khách hàng.</li>
                <li>- <strong>Trường hợp khách hàng cung cấp sai thông tin đặt hàng:</strong> December không chịu trách nhiệm đối với các đơn hàng giao sai do khách hàng cung cấp nhầm địa chỉ, số điện thoại hoặc thông tin sản phẩm.</li>
                <li>- <strong>Hỗ trợ khách hàng:</strong> Nếu có bất kỳ vấn đề nào liên quan đến chất lượng bánh hoặc đơn hàng, khách hàng vui lòng liên hệ hotline <strong>0103456789</strong> để được hỗ trợ kịp thời.</li>
                <li>- <strong>Chính sách hoàn tiền:</strong> December sẽ xem xét hỗ trợ hoàn tiền đối với những trường hợp sản phẩm bị lỗi hoặc không thể đổi sản phẩm phù hợp. Các trường hợp cụ thể sẽ được cửa hàng trao đổi và thống nhất với khách hàng.</li>
            </ul>

            <p class="fw-bold text-dark mt-3">December – Luôn đặt chất lượng sản phẩm và sự hài lòng của khách hàng lên hàng đầu!</p>';
        break;

    case 'usage':
        $title = "HƯỚNG DẪN BẢO QUẢN VÀ SỬ DỤNG BÁNH";
        $content = '
            <div class="usage-section">
                <h5 class="fw-bold text-dark mb-3">HƯỚNG DẪN CHUNG</h5>
                <ol class="ps-3 mb-4">
                    <li>Nên thưởng thức bánh ngay sau khi nhận hàng để cảm nhận trọn vẹn hương vị và độ mềm thơm của bánh.</li>
                    <li>Nếu chưa sử dụng ngay, vui lòng bảo quản bánh trong hộp kín hoặc bao bì phù hợp để tránh bụi bẩn và côn trùng.</li>
                    <li>Không để bánh dưới ánh nắng trực tiếp hoặc ở nơi có nhiệt độ cao vì có thể ảnh hưởng đến chất lượng và hương vị.</li>
                    <li>Không để bánh gần các loại thực phẩm có mùi mạnh để tránh bánh bị ám mùi.</li>
                    <li>Sử dụng dụng cụ sạch khi lấy bánh, hạn chế tiếp xúc trực tiếp bằng tay để đảm bảo vệ sinh.</li>
                    <li>Không sử dụng bánh khi có dấu hiệu bất thường về màu sắc, mùi vị hoặc kết cấu.</li>
                </ol>

                <h5 class="fw-bold text-dark mb-3">HƯỚNG DẪN KHI THƯỞNG THỨC</h5>
                <ul class="list-unstyled ps-3 mb-4">
                    <li>- Lấy bánh ra khỏi bao bì bằng dụng cụ sạch.</li>
                    <li>- Với bánh bảo quản lạnh, có thể để ở nhiệt độ phòng một thời gian ngắn trước khi thưởng thức nếu thành phần bánh cho phép.</li>
                    <li>- Không sử dụng lò vi sóng để làm nóng bánh có lớp phủ socola hoặc nhân kem.</li>
                    <li>- Nên dùng bánh cùng trà, cà phê hoặc sữa để tăng thêm trải nghiệm thưởng thức.</li>
                </ul>

                <p class="fw-bold text-dark mt-4">December – Bảo quản đúng cách, thưởng thức trọn vị ngọt ngào!</p>
            </div>';
        break;

    case 'about':
    $title = "VỀ CHÚNG TÔI";
    $content = "
        <div class='about-page'>
            <div class='container'>
                <div class='row align-items-center mb-5 pb-5'>
                    <div class='col-md-6 pe-md-5'>
                        <h2 class='fw-bold mb-4' style='color: #a31d1d;'>1. Khởi nguồn từ niềm đam mê bánh ngọt</h2>
                        <p class='lead italic mb-3' style='font-size: 1.1rem; color: #555;'>\"Mỗi chiếc bánh ngon đều được tạo nên từ những nguyên liệu chất lượng và cả tình yêu của người làm bánh.\"</p>
                        <p style='text-align: justify; line-height: 1.8; color: #333;'>
                            December ra đời từ niềm đam mê với những chiếc bánh donut xinh xắn và mong muốn mang đến những khoảnh khắc ngọt ngào cho mọi người. Chúng tôi tin rằng một chiếc bánh nhỏ không chỉ đơn thuần là món ăn mà còn có thể mang đến niềm vui, sự thư giãn và những cảm xúc đáng nhớ trong cuộc sống.
                        </p>
                        <p style='text-align: justify; line-height: 1.8; color: #333;'>
                            Từ những chiếc donut truyền thống đến các hương vị sáng tạo, mỗi sản phẩm tại December đều được chăm chút để mang đến trải nghiệm thưởng thức thật đặc biệt.
                        </p>
                    </div>
                    <div class='col-md-6'>
                        <img src='uploads/about_story.jpg' class='img-fluid rounded-4 shadow-lg' alt='December Story'>
                    </div>
                </div>

                <div class='row align-items-start mb-5 pb-5'>
                    <div class='col-md-6 mb-4 mb-md-0'>
                        <img src='uploads/about_material.jpg' class='img-fluid rounded-3 shadow-sm' style='height: 350px; width: 100%; object-fit: cover;' alt='Material'>
                    </div>

                    <div class='col-md-6 ps-md-5'>
                        <h2 class='fw-bold mb-4' style='color: #a31d1d;'>2. Sự tận tâm trong từng chiếc bánh</h2>

                        <div class='mb-4'>
                            <h5 class='fw-bold mb-2' style='color: #333;'>Nguyên liệu chất lượng</h5>
                            <p style='text-align: justify; line-height: 1.8; color: #555;'>
                                December luôn chú trọng lựa chọn những nguyên liệu tươi ngon, có nguồn gốc rõ ràng, đảm bảo hương vị thơm ngon và chất lượng trong từng sản phẩm.
                            </p>
                        </div>

                        <div class='mb-4'>
                            <h5 class='fw-bold mb-2' style='color: #333;'>Hương vị đa dạng</h5>
                            <p style='text-align: justify; line-height: 1.8; color: #555;'>
                                Từ donut truyền thống, donut socola, Oreo mochi đến tiramisu và nhiều hương vị độc đáo khác, chúng tôi không ngừng sáng tạo để mang đến những trải nghiệm mới mẻ cho khách hàng.
                            </p>
                        </div>

                        <div class='mb-4'>
                            <h5 class='fw-bold mb-2' style='color: #333;'>Chế biến tỉ mỉ</h5>
                            <p style='text-align: justify; line-height: 1.8; color: #555;'>
                                Mỗi chiếc bánh đều được chăm chút từ khâu chuẩn bị nguyên liệu, tạo hình đến trang trí. Chúng tôi luôn chú trọng từng chi tiết để bánh không chỉ ngon miệng mà còn đẹp mắt.
                            </p>
                        </div>
                    </div>
                </div>

                <div class='text-center py-5 mb-5' style='background: #FAF9F6; border-radius: 30px;'>
                    <h2 class='fw-bold mb-4' style='color: #a31d1d;'>3. Hành trình lan tỏa sự ngọt ngào</h2>
                    <p class='mx-auto px-3 mb-3' style='max-width: 800px; text-align: justify; line-height: 1.8; color: #333;'>
                        December mong muốn trở thành một phần nhỏ trong những khoảnh khắc vui vẻ của bạn. Dù là bữa sáng nhẹ nhàng, buổi trà chiều cùng bạn bè hay món quà bất ngờ dành tặng người thân, những chiếc donut của chúng tôi luôn sẵn sàng góp thêm chút ngọt ngào.
                    </p>
                    <p class='mx-auto px-3 mb-3' style='max-width: 800px; text-align: justify; line-height: 1.8; color: #333;'>
                        Với phương châm đặt chất lượng và sự hài lòng của khách hàng lên hàng đầu, December không ngừng hoàn thiện sản phẩm, đa dạng hương vị và nâng cao chất lượng phục vụ.
                    </p>
                    <p class='fw-bold mt-4 mb-0' style='color: #a31d1d; font-size: 1.05rem; text-align: center;'>December – Trao vị ngọt, gửi yêu thương!</p>
                </div>
            </div>
        </div>

        <style>
            .about-page { color: #333; overflow-x: hidden; }
            .about-page p { text-align: justify; line-height: 1.8; }
            .rounded-4 { border-radius: 2rem !important; }
        </style>
    ";
    break;
}

?>
<!-- Giao diện hiển thị (Breadcrumb giống ảnh mẫu) -->

<div class="container mt-5 mb-5" style="min-height: 500px;">
    <h5 class="fw-bold text-uppercase mb-4"><?php echo $title; ?></h5>
    <div class="policy-body" style="line-height: 1.8; font-size: 15px;">
        <?php echo $content; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
