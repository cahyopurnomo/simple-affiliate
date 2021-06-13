jQuery(document).ready(function () {
   // CONVERT INTO UPPERCASE
   /*
   jQuery('input[type=text]').bind('change keyup keydown', function () {
      this.value = this.value.toUpperCase();
   });

   jQuery('#uname, #passwd').bind('change keyup keydown', function () {
      this.value = this.value.replace(/\s/g, ""); //remove spasi whit space
      this.value = this.value.toLowerCase();
   });
   */
   jQuery('#btnSubmit').click(function () {
      if (confirm('Pastikan data Anda benar ! Daftar Sekarang ?')) {
         return true;
      } else {
         return false;
      }
   });

   // SELECT PROPINSI WHEN KOTA SELECTED
   jQuery('.cboCity').change(function () {
      
      province = jQuery('option:selected', this).attr('data-province');
      
      jQuery(".user_province").val(province);
      // jQuery("#cboProvince option[value='" + province + "']").attr("selected", "selected");
   });
})