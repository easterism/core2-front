var staff = {
	sync : function() {
		$("#main_staff_staff_error").hide();
		$.ajax("index.php?module=example&get=ldap")
			.done(function(data) {
				if (data) {
					$("#main_staff_staff_error").html(data);
					$("#main_staff_staff_error").show();
				}
				else load("index.php?module=example&action=staff");
			})
			.fail(function() {
				alert("Error!");
			})
	}
}
