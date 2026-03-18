</main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmLogout() {
    Swal.fire({
        title: 'ออกจากระบบ?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#003366',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) location.href = '../logout.php';
    });
}
</script>
</body>
</html>