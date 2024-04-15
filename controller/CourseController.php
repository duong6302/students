<?php
require 'model/CourseModel.php';

$m = trim($_GET['m'] ?? 'index'); // trim : xoa khoang trang 2 dau
$m = strtolower($m); // chuyen ve chu thuong
switch ($m) {
    case 'index':
        index();
        break;
    case 'add':
        Add();
        break;
    case 'handle-add':
        handleAdd();
        break;
    case 'delete':
        handleDelete();
        break;
    case 'edit':
        edit();
        break;
    case 'handle-update':
        handleUpdate();
        break;
    default:
        index();
        break;
}
function handleUpdate()
{
    if (isset($_POST['btnUpdate'])) {
        $id = $_GET['id'] ?? null;
        $id = is_numeric($id) ? $id : 0;

        $name = trim($_POST['name'] ?? null);
        $name = strip_tags($name);

        $description = trim($_POST['description'] ?? null);
        $description = strip_tags($description);

        $status = trim($_POST['status'] ?? null);
        $status = $status === '0' || $status === '1' ? $status : 0;

        $beginningDate = trim($_POST['date_beginning'] ?? null);
        $beginningDate = date('Y-m-d', strtotime($beginningDate));
        
        $info = getDetailCourseById($id);
        // check du lieu
        $_SESSION['error_course'] = [];
        // tien hanh upload logo cua khoa
        $logo = $info['logo'] ?? null;

        $_SESSION['error_course']['logo'] = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            // thuc su nguoi dung muon upload logo
            $logo = uploadFile(
                $_FILES['logo'],
                'public/uploads/images/',
                ['image/png', 'image/jpeg', 'image/jpg', 'image/svg'],
                3 * 1024 * 1024
            );
            
            if (empty($logo)) {
                $_SESSION['error_course']['logo'] = 'Type file is allow .png, .jpg, .jpeg, .svg and size file <= 3Mb';
            } else {
                $_SESSION['error_course']['logo'] = null;
            }
        }
        if (empty($name)) {
            $_SESSION['error_course']['name'] = 'Enter name, please';
        } else {
            $_SESSION['error_course']['name'] = null;
        }
        if (empty($description)) {
            $_SESSION['error_course']['description'] = "Enter description, please";
        } else {
            $_SESSION['error_course']['description'] = null;
        }

        $checkError = false;
        foreach($_SESSION['error_course'] as $error){
            if(!empty($error)){
                $checkError = true;
                break;
            }
        }
        if($checkError){
            // co loi xay ra
            // quay lai form update
            header("Location:index.php?c=course&m=edit&id={$id}");
        } else {
            // khong co loi gi ca
            // tien hanh update vao database
            if(isset($_SESSION['error_course'])){
                unset($_SESSION['error_course']);
            }
            $slug = slugify($name);
            
            $update = updateCourseById(
                $name,
                $slug,
                $description,
                $status,
                $beginningDate,
                $logo,
                $id
            );
            if($update){
                // thanh cong
                header("Location:index.php?c=course&state=success");
            } else {
                // that bai
                header("Location:index.php?c=course&m=edit&id={$id}&state=failure");
            }
        }
    }
}
function edit()
{
    $id = trim($_GET['id'] ?? null);
    $id = is_numeric($id) ? $id : 0; // is_numeric : kiem tra gia tri co phai la so hay ko?
    $infoDetail = getDetailCourseById($id);
    if (!empty($infoDetail)) {
        // co du lieu trong database
        // hien thi thong tin du lieu
        require APP_PATH_VIEW . 'courses/edit_view.php';
    } else {
        // khong co du lieu
        // thong bao loi
        require APP_PATH_VIEW . 'error_view.php';
    }
}
function handleDelete()
{
    $id = trim($_GET['id'] ?? null);
    $delete = deleteCourseById($id);
    if ($delete) {
        header("Location:index.php?c=course&state=delete_success");
    } else {
        header("Location:index.php?c=course&state=delete_failure");
    }
}
function handleAdd()
{
    if (isset($_POST['btnSave'])) {
        $name = trim($_POST['name'] ?? null);
        $name = strip_tags($name);

        $description = trim($_POST['description'] ?? null);
        $description = strip_tags($description);

        $status = trim($_POST['status'] ?? null);
        $status = $status === '0' || $status === '1' ? $status : 0;

        $beginningDate = trim($_POST['date_beginning'] ?? null);
        $beginningDate = date('Y-m-d', strtotime($beginningDate));

        // check du lieu
        $_SESSION['error_course'] = [];
        // tien hanh upload logo cua khoa
        $logo = null;
        $_SESSION['error_course']['logo'] = null;
        if (!empty($_FILES['logo']['tmp_name'])) {
            // thuc su nguoi dung muon upload logo
            $logo = uploadFile(
                $_FILES['logo'],
                'public/uploads/images/',
                ['image/png', 'image/jpeg', 'image/jpg', 'image/svg'],
                3 * 1024 * 1024
            );
            if (empty($logo)) {
                $_SESSION['error_course']['logo'] = 'Type file is allow .png, .jpg, .jpeg, .svg and size file <= 3Mb';
            } else {
                $_SESSION['error_course']['logo'] = null;
            }
        }
        if (empty($name)) {
            $_SESSION['error_course']['name'] = 'Enter name, please';
        } else {
            $_SESSION['error_course']['name'] = null;
        }
        if (empty($description)) {
            $_SESSION['error_course']['description'] = "Enter description , please";
        } else {
            $_SESSION['error_course']['description'] = null;
        }

        if (
            !empty($_SESSION['error_course']['name'])
            ||
            !empty($_SESSION['error_course']['description'])
            ||
            !empty($_SESSION['error_course']['logo'])
        ) {
            // co loi - thong bao ve lai form add
            header("Location:index.php?c=course&m=add&state=fail");
        } else {
            // insert vao database
            if (isset($_SESSION['error_course'])) {
                unset($_SESSION['error_course']);
            }
            $insert = insertCourse($name, $description, $status, $beginningDate, $logo);
            if ($insert) {
                header("Location:index.php?c=course&state=success");
            } else {
                header("Location:index.php?c=course&m=add&state=error");
            }
        }
    }
}
function Add()
{

    require APP_PATH_VIEW . 'courses/add_view.php';
}
function index()
{
    $keyword = trim($_GET['search'] ?? null);
    $keyword = strip_tags($keyword);
    $page    = trim($_GET['page'] ?? null);
    $page    = (is_numeric($page) && $page > 0) ? $page : 1;
    $linkPage = createLink([
        'c' => 'course',
        'm' => 'index',
        'page' => '{page}',
        'search' => $keyword
    ]);
    $itemCourses = getAllDataCourses($keyword);
    $itemCourses = count($itemCourses);
    $pagination = pagination($linkPage, $itemCourses, $page, LIMIT_ITEM_PAGE);
    $start = $pagination['start'] ?? 0;
    $courses = getAllDataCoursesByPage($keyword, $start, LIMIT_ITEM_PAGE);
    $htmlPage = $pagination['htmlPage'] ?? null;
    require APP_PATH_VIEW . 'courses/index_view.php';
}
