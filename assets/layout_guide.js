// Show/hide layout guide when pressing "G"
document.addEventListener("keydown", (event) => {
    if (event.key === "g" || event.key === "G") {
        const layoutGuide = document.getElementById("dev-layout-guide");
        
        if (layoutGuide) {
            layoutGuide.classList.toggle("dev-layout-guide");
        }
        
    }
});