function previewImage(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            document.getElementById('logoPreview').src = ev.target.result;
        }
        reader.readAsDataURL(file);
    }
}
