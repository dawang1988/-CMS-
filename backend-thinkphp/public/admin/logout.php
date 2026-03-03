<?php
session_start();
session_destroy();
?>
<script>localStorage.removeItem('admin_token');window.location.href='login.php';</script>
