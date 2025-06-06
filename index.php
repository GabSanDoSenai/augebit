<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AUGEBIT</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --roxo-principal: #3e206e;
      --roxo-claro: #7f75e3;
      --roxo-gradiente: linear-gradient(135deg, #a8a2f1, #6a5acd);
      --branco: #ffffff;
      --sombra: 0 10px 30px rgba(62, 32, 110, 0.2);
      --transicao: all 0.4s cubic-bezier(0.65, 0, 0.35, 1);
    }

/* Garante que não há margens ou paddings indesejados */
body {
  margin: 0;
  padding: 0;
  overflow-x: hidden; /* Evita barras de rolagem horizontais */
}

/* Remove qualquer espaço extra após o rodapé */
html, body {
  height: 100%;
}
    * {
      padding: 0;
      margin: 0;
      box-sizing: border-box;
    }

    @font-face {
      font-family: 'Black';
      src: url(fontes/Poppins/Poppins-Black.ttf);
    }
    @font-face {
      font-family:'SemiBold' ;
      src: url(fontes/Poppins/Poppins-SemiBold.ttf);
    }

   
    @font-face {
      font-family: 'Italic';
      src: url(fontes/Poppins/Poppins-Italic.ttf)
    }
    @font-face {
      font-family: 'Medium';
      src: url(fontes/Poppins/Poppins-Medium.ttf);
    }
    
    @font-face {
      font-family:  'Light';
      src: url(fontes/Poppins/Poppins-Light.ttf);
    }

    /* Estilos da Página Principal - CORRIGIDOS */
    .hero {
      font-family: 'Medium';
     background: linear-gradient(20deg, #9999FF, #9999FF, #4747d9 );
  height: 100vh;
  margin: 0;
  color: white;
      height: 100vh;
      overflow: hidden;
      position: relative;
      width: 100%;
      padding: 20px;
    }
   @keyframes waveGradient {
  0% {
    background-position: 0% 50%;
  }
  25% {
    background-position: 50% 75%;
  }
  50% {
    background-position: 100% 50%;
  }
  75% {
    background-position: 50% 25%;
  }
  100% {
    background-position: 0% 50%;
  }
}
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      position: relative;
      z-index: 10;
      padding: 20px 0;
    }

    .logo-header {
      display: flex;
      align-items: center;
    }

    .logo-header img {
      position: relative;
      width: 100px;
      height: 80px;
      top:-10px; 
    }

    .logo-header h1 {
      font-size: 30px;
      font-family: 'Light';
    }

    nav ul {
      padding-right: 100px;
      display: flex;
      gap: 80px;
      list-style: none;
    }

    nav a {
      color: rgb(255, 255, 255);
      text-decoration: none;
      font-size: 19px;

      font-family: 'Light';
      transition: all 0.3s ease;
    }

    nav a:hover {
      border-bottom: 2px solid white;
    }

    .active {
      color: rgba(255, 255, 255, 0.803);
    }

    .acount {
      color: rgba(255, 255, 255, 0.803);
    }

    .contato {
      color: rgba(255, 255, 255, 0.803);
    }

    /* Layout principal corrigido */
    
      .hero {
  height: 100vh; /* Isso força uma altura fixa */
  overflow: hidden; /* Isso corta o conteúdo que excede */
}
    
    .hero-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 100%;
      height: calc(100% - 60px);
      position: relative;
      padding: 0 5%;
    }

    /* Lado esquerdo */
    .left-content {

      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .italic p {
      font-size:60px; 
      width: 700px;
      line-height: 1.1;
      margin-bottom:100px;
      font-family: "Medium";
    }
.italic p em{
  color:#3e206e;
  
}
.buttons button {
  font-family: 'Medium';
}
    .buttons {
      margin-top: 80px;
      display: flex;
      margin-left: 50px;
      gap: 20px;
    }

    button {
      padding: 10px 45px;
      width: 250px;
      border-radius: 50px;
      border: none;
      color: rgb(255, 255, 255);
      font-size: 15px;
      cursor: pointer;
      border: solid #ffffff05;
     background: radial-gradient(circle, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0.307) 100%);
  backdrop-filter: blur(30px);
  -webkit-backdrop-filter: blur(5px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.111);
    }

    button:hover {
      background: rgba(255, 255, 255, 0.35);
    }
section {
  scroll-margin-top: 80px; }
    /* Lado direito */
    .right-content {
      width: 45%;
      position: relative;
      height: 100%;
      top: 10px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      
    }

    .cube-container {
      perspective: 900px;
      position: absolute;
      top: 20%;
      left: 50%;
      transform: translate(-50%, -50%);
      z-index: 2;
    }

    .cube {
      width: 100px;
      height: 100px;
      position: relative;
      transform-style: preserve-3d;
      animation: rotate 18s infinite linear;
    }

    .face {
      position: absolute;
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #ffffff1e);
      opacity: 0.5;
      border: 2px solid rgb(255, 255, 255);
    }

    .front  { transform: translateZ(50px); }
    .back   { transform: rotateY(180deg) translateZ(50px); }
    .right  { transform: rotateY(90deg) translateZ(50px); }
    .left   { transform: rotateY(-90deg) translateZ(50px); }
    .top    { transform: rotateX(90deg) translateZ(50px); }
    .bottom { transform: rotateX(-90deg) translateZ(50px); }

    @keyframes rotate {
      0% { transform: rotateX(0) rotateY(0); }
      100% { transform: rotateX(360deg) rotateY(360deg); }
    }

    .company-description {
      position: relative;
      z-index: 1;
  
       margin-top: 150px;
        text-align: center;
    }

    .company-description p {
      font-size: 20px;
font-family: 'Light';
      color: #f9f9ffe3;
      line-height: 1.4;
      text-align: center;
    }

   

    /* Responsividade */
    @media (max-width: 992px) {
      .hero-content {
        flex-direction: column;
        padding: 40px 20px;
        height: auto;
      }

      .left-content, .right-content {
        width: 100%;
        text-align: center;
      }

      .buttons {
        justify-content: center;
      }



      .cube-container {
                position: relative;
        top: 590px;
        left: -140px;
        transform: none;
       
      }
    }

    @media (max-width: 768px) {
      nav ul {
        padding-right: 20px;
        gap: 30px;
      }

      

      .company-description p {
        font-size: 18px;
      }


      button {
        padding: 12px 30px;
        font-size: 16px;
    
      }
    }

    @media (max-width: 576px) {
      .italic p {
        font-size: 40px;
      }

      .buttons {
        flex-direction: column;
        gap: 15px;
        
      }

    
      button {
        width: 100%;
      }
    }
        body {
      font-family: 'Segoe UI', sans-serif;
      line-height: 1.6;
      color: var(--roxo-principal);
      overflow-x: hidden;
      scroll-behavior: smooth;
    }

    section {
      padding: 80px 20px;
      position: relative;
      overflow: hidden;
    }
 .trabalhos h3 {
  position: relative;
  right: 130px;
  text-align: left;
  margin-left: 50px;
  font-size: 40px;
  margin-top: 130px;
  margin-right: 30px;
  margin-bottom: 40px;
  color: var(--roxo-principal);
  font-family: 'SemiBold';
  position: relative;
  z-index: 2;

 }
    h2, h3 {
      text-align: center;
      font-weight: normal;
      letter-spacing: 1px;
      text-transform: none;
      color: var(--roxo-principal);
      margin-bottom: 1.5rem;
      position: relative;
      font-family: 'SemiBold';
      font-size: 40px;
    }

    h2::after, h3::after {
      content: '';
      display: block;
      width: 60px;
      height: 2px;
      background: var(--roxo-gradiente);
      margin: 15px auto 30px;
      border-radius: 2px;
    }

    /* Seção Quem Somos */
    .quemsomos {
      background-color: var(--branco);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      position: relative;
    }

    .quemsomos::before {
      content: '';
      position: absolute;
      top: -50px;
      right: -50px;
      width: 200px;
      height: 100px;
      background: var(--roxo-gradiente);
      border-radius: 50%;
      opacity: 0.2;
      z-index: 0;
    }

    .content-section {
      display: flex;
      font-family: 'Medium';
      align-items: center;
      gap: 60px;
      max-width: 1200px;
      margin: 0 auto;
      position: relative;
      z-index: 1;
      flex-wrap: wrap;
      justify-content: center;
    }

    .textos h3 {
      font-size: 2.5rem;
      font-family: 'SemiBold';
      line-height: 1.2;
      text-align: left;
      margin-bottom: 2rem;
     
      text-transform: none;
     
      color: var(--roxo-principal);
    }

    .textos h3::after {
      margin-left: 0;
    }

    .imagem1 img {
      max-width: 100%;
      height: auto;
      border-radius: 15px;
     
    }

    .imagem1:hover img {
      transform: perspective(1000px) rotateY(0deg);
    }

    .somos {
      max-width: 500px;
      text-align: left;
      color: var(--roxo-principal);
      font-size: 20px;
    
    }

    /* Seção Clientes */
   .clientes {
  padding: 120px 20px 80px; /* Aumenta o padding superior */
  background: #fff;
  position: relative;
  overflow: visible; /* Mude de hidden para visible */
  z-index: 2;
}

.tudo {
  background: #fff;
  position: relative;
  z-index: 3;
  margin-top: -190px; /* Reduz o margin negativo */
  padding: 40px 20px;
  border-radius: 15px;
}

    .tudo::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
      transform: rotate(30deg);
      z-index: 1;
    }

    .clientes h2 {
      color: #3e206e;
      font-size: 1.8rem;
      margin-bottom: 50px;
      position: relative;
      z-index: 1;
      font-family: 'SemiBold';
      font-size: 40px;
    }

    .clientes h2::after {
      background: var(--branco);
      width: 100px;
      height: 2px;
      margin: 10px auto;
    }
/* Seção Clientes */
.clientes {
  padding: 80px 20px;
  background: #fff;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
  display: flex;
  padding-top: 60px; /* Espaço extra no topo */
}
.titulo-clientes {
  margin-right: 280px;
  margin-left:90px;
  font-size: 2.5rem;
  color: var(--roxo-principal);
  font-family: 'SemiBold';
  text-transform: none;
  margin-top: 3ch;
  letter-spacing: 3px;
}


.lista-clientes {

  padding-top: 40px; /* Espaço para as bolas */
  position: relative;
  z-index: 3;
  display: flex;
}

.cliente-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 200px;
}


.bola-roxa {
  width: 100px; /* Aumente um pouco o tamanho */
  height: 100px;
  background: #9999FF;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: -30px auto 20px; /* Ajuste o margin */
  position: relative;
  z-index: 4;
  box-shadow: 0 8px 25px rgba(62, 32, 110, 0.4);
}

.logo-cliente {
  max-width: 55px;
  max-height: 55px;
  filter: brightness(0) invert(1);
}

.nome-cliente {
  color: var(--roxo-principal);
  font-family: 'SemiBold';
  font-size: 1.0rem;
  margin-bottom: 10px;
  text-transform: uppercase;
}



/* Responsivo */
@media (max-width: 768px) {
  .container {
    flex-direction: column;
    gap: 30px;
  }
  
  .titulo-clientes {
    writing-mode: horizontal-tb;
    transform: none;
    text-align: left;
    padding-right: 20px;
  }
  
  .bola-roxa {
    width: 100px;
    height: 100px;
  }
  
  .logo-cliente {
    max-width: 50px;
    max-height: 50px;
  }
}


    /* Seção Trabalhos */
   

    .galeria-trabalhos {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 30px;
      margin-top: 40px;
    }

    .trabalho-item {
      background-color: #9999ffb8;
      border-radius: 1px solid #4b4b79b8
      ;
      align-items: center;
      position: relative;
      overflow: hidden;
      border-radius: 15px;
      box-shadow: var(--sombra);
      transition: var(--transicao);
      max-width: 450px;
      padding: 80px;

    }

    .trabalho-item:hover {
      transform: translateY(-10px);
    }
       .trabalho-item p{
        font-size: 18px;
        width: 380px;
       padding-right: 80px;
        
       
       }

    .trabalho-item img {
      width: 100%;
      height: auto;
      margin-bottom:30%;
      display: block;
      transition: var(--transicao);
    }

    .trabalho-item:hover img {
      transform: scale(1.05);
    }
    .trabalhos {
      padding: 80px 20px;
      text-align: center;
      background-color: #ffffff;
      display: flex;
  position: relative;
  overflow: visible;
  min-height: 600px;
    }

    
    .carrossel-container {
      max-width: 900px;
      margin: 40px auto 0;
      position: relative;
      overflow: hidden;
    }

    .carrossel {
      display: flex;
      transition: transform 0.4s ease;
    }

    .slide {
      min-width: 100%;
      padding: 0 20px;
      box-sizing: border-box;
    }

    .slide-content {
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .slide img {
      max-width: 100%;
      max-height: 400px;
      object-fit: contain;
      margin-bottom: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .slide-text {
      max-width: 600px;
      color: #3e206e;
      font-family: 'Medium';
      font-size: 18px;
      line-height: 1.6;
      text-align: center;
      opacity: 0;
      height: 0;
      overflow: hidden;
      transition: opacity 0.4s ease, height 0.4s ease;
    }

    .slide.active .slide-text {
      opacity: 1;
      height: auto;
      margin-top: 20px;
    }

    .carrossel-nav {
      display: flex;
      justify-content: center;
      margin-top: 30px;
      gap: 15px;
    }

    .nav-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: #9999FF;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .nav-dot.active {
      background: #3e206e;
      transform: scale(1.2);
    }
    /* Seção Compromisso */
    .compromisso {
      display: flex;
      justify-content: center;
      align-items: center;
      background:#ffffff;
      color: var(--branco);
      flex-wrap: wrap;
      max-height: 500px;
      padding: 70px;
      position: relative;
      overflow: hidden;
      color: #3e206e;
    }

    .compromisso::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      
      opacity: 0.5;
    }



    .textoreal {
      max-width: 800px;
      text-align: justify;
      font-size: 1.1rem;
      line-height: 1.8;
      animation: fadeInRight 1s ease-out;
      position: relative;
      z-index: 2;
      padding: 30px;
      margin-right: 30px;
      border-radius: 12px;
   
    }

    .textoreal p {
     font-family: 'Medium';
  font-size: 20px;
  text-align: center;
    }

    p{
      font-family: 'Medium';
    }
h3{
  font-family: 'SemiBold';
}
    

    /* Seção Contatos */
    .contatos {
      background-color: var(--branco);
      text-align: center;
      justify-content: center;
      padding: 90px 70px;
      height: 300px;
    }

    .contato-section {
  position: relative;
  z-index: 10; 
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      max-width: 1000px;
      margin: 0 auto;
    }

    .trabalhos p{
      font-family: '  Medium';
    }
    .texto h3 {

      position: relative;
      color: #3e206e;
      font-size: 1.8rem;
      text-align: left;
      text-transform: none;
      letter-spacing: normal;
      
    }

    .redes {
      display: flex;
      gap: 30px;
      flex-wrap: wrap;
      justify-content: center;
    }

    .rede {
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: var(--transicao);
      padding: 20px;
      border-radius: 10px;
    }

    .rede:hover {
      background: rgba(126, 117, 227, 0.1);
      transform: translateY(-5px);
    }

    .rede img {
      width: 40px;
      height: 40px;
      margin-bottom: 10px;
    }

    .rede p {
      font-weight: normal;
      color: var(--roxo-claro);
      font-size: 0.9rem;
      font-family: 'Medium';
    }

    /*dverdade*/

.dverdade {
  width: 100%;
  min-height: 400px;
  height: auto;
  padding: 40px 20px;
  display: flex;
  align-items: center;
  justify-content: center;

}
.augebit{
  width: 700px;
}

.dverdade p {
  font-family: 'Medium';
  font-size: 20px;
  text-align: center;
}
.dverdade em {
  color: #ac89e6;
  font-style: normal;
  font-weight: 200
}

    /* Rodapé */
    .rodape {
  background-color: var(--roxo-claro);
  width: 100vw; /* Ocupa toda a largura da viewport */
  min-height: 300px; /* Altura mínima */
  padding: 60px 15px;
  color: var(--branco);
  display: flex;
  justify-content: space-around;
  align-items: center;
  flex-wrap: wrap;
  gap: 40px;
  position: relative;
  margin-top: 80px; /* Espaço antes do rodapé */
}

    .logo-rodape {
      text-align: center;
      display: flex;
      padding-left: 50px;
      flex-direction: column;
      align-items: center;
    }

    .logo-rodape img {
      width: 100px;
      margin-left: 20px;
      margin-bottom: 15px;
    }

    .logo-rodape h1 {
      font-size: 1.8rem;
      font-family: 'Medium';
      margin-bottom: 5px;
      color: var(--branco);
      font-weight: normal;
      margin-left: 25px;
    }

    .logo-rodape p {
      font-style:"light"; 
      font-size: 0.9rem;
       margin-left: 25px;
      
    }
.pages{
  margin-left: -110px;
}
    .pages p {
      margin-bottom: 15px;
      cursor: pointer;
      transition: var(--transicao);
      position: relative;
      font-size: 0.9rem;
      font-family: 'Light';
    }

    .pages p::after {
      content: '';
      position: absolute;
      bottom: -2px;
      left: 0;
      width: 0;
      height: 1px;
      background: var(--branco);
      transition: var(--transicao);
    }

    .pages p:hover::after {
      width: 100%;
    }

    .redescociais {
      display: flex;
      gap: 20px;
      align-items: center;
    }

    .redescociais img {
      width: 25px;
      height: 25px;
      filter: brightness(0) invert(1);
      transition: var(--transicao);
      cursor: pointer;
    }

    .redescociais img:hover {
      transform: scale(1.2);
    }; 

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}; 


    /* Responsividade */
    @media (max-width: 992px) {
      .content-section {
        flex-direction: column;
        text-align: center;
      }
      
      .textos h3 {
        text-align: center;
      }
      
      .textos h3::after {
        margin-left: auto;
        margin-right: auto;
      }
      
      .compromisso {
        flex-direction: column;
        gap: 40px;
        padding: 60px 40px;
      }
     
      
      .contato-section {
        flex-direction: column;
        gap: 40px;
      }
      
      .texto h3 {
        text-align: center;
      }

      .hero-content {
        flex-direction: column;
        padding: 40px 20px;
        height: auto;
      }

      .hero-text {
        max-width: 100%;
        margin: 0;
        text-align: center;
      }

      .cube-container {
        position: relative;
        top: auto;
        right: auto;
        transform: none;
        margin: 40px auto;
      }

      .buttons {
        transform: none;
        margin: 40px auto;
        justify-content: center;
      }
    }

    @media (max-width: 768px) {
      section {
        padding: 60px 20px;
      }
      
      .textos h3 {
        font-size: 2rem;
      }
      
      .empresas {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }
      
      .tudo {
        padding: 60px 20px;
      }
      
      .clientes h2 {
        font-size: 1.5rem;
      }
      
      .compromisso {
        padding: 60px 20px;
      }
      
      
      .rede {
        padding: 15px;
      }

      header {
        flex-direction: column;
        padding: 20px;
      }

      nav ul {
        margin-top: 20px;
        gap: 15px;
      }

      nav a {
        font-size: 20px;
      }

      .logo-header h1 {
        font-size: 50px;
      }

      .logo-header img {
        width: 80px;
        height: 70px;
      }

      .italic p {
        font-size: 50px;
        margin-top: 200px;
      }

      .company-description p {
        font-size: 18px;
      }

      button {
        padding: 12px 60px;
        font-size: 16px;
      }
    }

    @media (max-width: 576px) {
      .empresas {
        grid-template-columns: 1fr;
      }
      
      .empresa-item {
        padding: 25px;
        display: block;
      }
      
      .textinho h3 {
        font-size: 1.8rem;
      }
      
      .redes {
        gap: 15px;
      }
      
      .logo-rodape h1 {
        font-size: 1.5rem;
      }

      .italic p {
        font-size: 40px;
        margin-top: 150px;
      }

      .company-description p {
        font-size: 16px;
      }

      button {
        padding: 10px 40px;
        font-size: 14px;
      }

    .italic p em {
  color: #7f75e3;
}


}
.tudo p{
  font-family: 'Light';
  color: #3e206e;
}
.cont{
  display: block;
  width: 400px;
}

section.compromisso .textoreal p em {
  color: #7f75e3 !important;
  font-style: normal !important;
 
}
    
  </style>
  </style>
</head>
<body>
  <!-- Seção Hero Corrigida -->
  <section class="hero">
    <header>
      <div class="logo-header">    
        <img src="assets/images/logobranca.png" alt="Logo da empresa"> 
        <h1><strong>AUGEBIT</strong></h1>  
      </div>    
      <nav>       
        <ul>         
          <li><a href="#" class="active">Home</a></li>         
          <li><a href="#quem-somos" class="acount">Quem Somos</a></li>         
         <li><a href="#contato-section" class="contato">Conecte-se</a></li>    
        </ul>     
      </nav>   
    </header>    
    
    <div class="hero-content">
      <!-- Lado Esquerdo -->
      <div class="left-content">
        <div class="italic">
          <p>Eficiência<br><em>move</em> a indústria.</p>
        </div>
    
      </div>
      
      <!-- Lado Direito -->
      <div class="right-content">
        <div class="cube-container">       
          <div class="cube">         
            <div class="face front"></div>         
            <div class="face back"></div>         
            <div class="face right"></div>         
            <div class="face left"></div>         
            <div class="face top"></div>         
            <div class="face bottom"></div>       
          </div>     
        </div>
        <div class="company-description">
          <p>Somos uma empresa de gestão de projetos técnicos e artísticos com foco na inovação tecnológica e na personalização para empresas de diversos setores industriais presentes no mercado.</p>
        
        <div class="buttons">
          <a href="login.php">         
          <button>Entrar</button></a>
          <a href="login.php">      
          <button>Criar conta</button>
              </a>
        </div>      
      </div>
    </div>
  </section>

  <!-- Seções Adicionais -->
  <section id="quem-somos" class="quemsomos">
    <div class="content-section">
      <div class="textos">
        <h3>Quem <br>somos?</h3>
      </div>
      <div class="imagem1">
        <img src="assets/images/Captura de tela 2025-05-09 103240 (2) 1 (1).png" alt="Equipe Augebit">
      </div>
      <div class="somos">
        <div class="escrever">
          <span class="maquina">Somos uma empresa de gestão de projetos técnicos e artísticos com foco na inovação tecnológica e na personalização para empresas de diversos setores industriais presentes no mercado.</span>
        </div>
      </div>
    </div>
  </section>
 
  <section class="compromisso">
   
    <div class="textoreal">
      <p>Desenvolver produtos exige conhecimento <em>técnico</em> e  <em>estratégico</em>, onde em cada etapa profissionais qualificados analisam, pesquisam e testam, encontrando assim a  <em>melhor</em> aplicação de um determinado produto, visando sua funcionalidade e estética de acordo com o seu objetivo principal de uso.<em> A Augebit busca cumprir seu propósito com compromisso e dedicação.</em></p>
    </div>
  </section>

 <section class="trabalhos">
   
    <div class="carrossel-container">
      <div class="carrossel">
        <!-- Slide 1 -->

        
        <div class="slide active">
          <div class="slide-content">
            <img src="assets/images/Frame 7.png" alt="Trabalho 1">
            <div class="slide-text">
              Solução completa de design técnico para frascos de perfume, combinando estética refinada com funcionalidade industrial
            </div>
          </div>
        </div>
        
        <!-- Slide 2 -->
        <div class="slide">
          <div class="slide-content">
            <img src="assets/images/Frame 6.png" alt="Trabalho 2">
            <div class="slide-text">
              Modelagem 3D precisa para hélices industriais, otimizando desempenho aerodinâmico e eficiência produtiva
            </div>
          </div>
        </div>
        
        <!-- Slide 3 -->
        <div class="slide">
          <div class="slide-content">
            <img src="assets/images/Frame 5.png" alt="Trabalho 3">
            <div class="slide-text">
              Desenvolvimento técnico de engrenagens industriais com tolerância micrométrica para aplicações de alta performance
            </div>
          </div>
        </div>
      </div>
          <div class="carrossel-nav">
      <div class="nav-dot active" data-index="0"></div>
      <div class="nav-dot" data-index="1"></div>
      <div class="nav-dot" data-index="2"></div>
     
    </div>

    </div>
    </div>
          <h3>Nossos Trabalhos</h3>
   
  </section>

<section class="dverdade">
 <div class="augebit">

  <p>
    Na Augebit, somos especialistas em  <em>transformar</em> ideias em desenhos técnicos precisos e profissionais, prontos para a produção. Desenvolvemos projetos em 2D e 3D com <em>agilidade</em>, seguindo todas as normas técnicas, garantindo segurança e funcionalidade.
Utilizamos softwares de ponta e contamos com uma <em>equipe qualificada</em> para entregar soluções confiáveis, seja para peças, sistemas mecânicos ou estruturas completas. Com a gente, <em>seu projeto sai do papel com qualidade e eficiência.</em>


  </p>
  </div>
</section>

 
 

<section id="contato-section" class="contatos">

    <div class="contato-section">
      <div class="texto">
        <h3>Entre em Contato</h3>
      </div>
      <div class="redes">
        <div class="rede">
          <img src="assets/images/ic_outline-email.png" alt="Email">
          <p>Email</p>
        </div>
        <div class="rede">
          <img src="assets/images/uil_linkedin.png" alt="Linkedin">
          <p>Linkedin</p>
        </div>
        <div class="rede">
          <img src="assets/images/ic_baseline-whatsapp.png" alt="Whatsapp">
          <p>Whatsapp</p>
        </div>
        <div class="rede">
          <img src="assets/images/mdi_instagram.png" alt="Instagram">
          <p>Instagram</p>
        </div>
      </div>
    </div>
  </section>

  <footer class="rodape">
    <div class="pages">
      <p>Home</p>
      <p>Quem Somos</p>
      <p>Nossos serviços</p>
      <p>Entre em contato</p>
    </div>
    <div class="logo-rodape">
      <img src="assets/images/logobranca.png" alt="Logo Augebit">
      <h1>AUGEBIT</h1>
      <p>Industrial design</p>
    </div>
    <div class="redescociais">
      <img src="assets/images/emailwhite.png" alt="Email">
      <img src="assets/images/instawhite.png" alt="Instagram">
      <img src="assets/images/likendinwhite.png" alt="Linkedin">
      <img src="assets/images/zapwhite.png" alt="Whatsapp">
    </div>
  </footer>

  <script>
    // Script para o carrossel minimalista
    document.addEventListener('DOMContentLoaded', function() {
      const carrossel = document.querySelector('.carrossel');
      const slides = document.querySelectorAll('.slide');
      const dots = document.querySelectorAll('.nav-dot');
      let currentIndex = 0;
      const slideCount = slides.length;

      // Função para atualizar o carrossel
      function updateCarrossel() {
        carrossel.style.transform = `translateX(-${currentIndex * 100}%)`;
        
        // Atualiza os slides ativos
        slides.forEach((slide, index) => {
          slide.classList.toggle('active', index === currentIndex);
        });
        
        // Atualiza os dots de navegação
        dots.forEach((dot, index) => {
          dot.classList.toggle('active', index === currentIndex);
        });
      }

      // Navegação pelos dots
      dots.forEach(dot => {
        dot.addEventListener('click', function() {
          currentIndex = parseInt(this.getAttribute('data-index'));
          updateCarrossel();
        });
      });

      // Auto-play (opcional - pode remover se não quiser)
      setInterval(() => {
        currentIndex = (currentIndex + 1) % slideCount;
        updateCarrossel();
      }, 5000);
    });
  


window.addEventListener('scroll', animateOnScroll);
window.addEventListener('load', animateOnScroll);
</script>
</body>
</html>