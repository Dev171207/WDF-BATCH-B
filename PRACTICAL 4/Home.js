

function welcome()
{
    alert("Welcome to Student Hub!");
}




function closeNotification()
{
    document.getElementById("notification").style.display = "none";
}




function closeModal()
{
    document.getElementById("myModal").style.display = "none";
}




function toggleMenu()
{
    var menu = document.getElementById("menu");

    if(menu.style.display == "block")
    {
        menu.style.display = "none";
    }
    else
    {
        menu.style.display = "block";
    }
}




function changeTheme()
{
    document.body.classList.toggle("dark");
}




var images = [

    "https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR2OVc_ee3-e-VZIkZXmaoOUgjnpdjEIDHn6brAqGub3A&s=10",

    "https://images.unsplash.com/photo-1523050854058-8df90110c9f1",

    "https://images.unsplash.com/photo-1523240795612-9a054b0db644"

];

var currentImage = 0;




function nextImage()
{
    currentImage++;

    if(currentImage >= images.length)
    {
        currentImage = 0;
    }

    document.getElementById("sliderImage").src =
        images[currentImage];
}




function previousImage()
{
    currentImage--;

    if(currentImage < 0)
    {
        currentImage = images.length - 1;
    }

    document.getElementById("sliderImage").src =
        images[currentImage];
}