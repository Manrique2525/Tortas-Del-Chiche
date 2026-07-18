@echo off
chcp 65001 >nul
title Subiendo Las Tortas Del Chiche a IONOS...
color 0A

echo ================================================
echo   LAS TORTAS DEL CHICHE - Deploy a IONOS
echo ================================================
echo.
echo  Servidor: access-5017867763.webspace-host.com
echo  Protocolo: SFTP (Puerto 22)
echo  Usuario: a266392
echo.
echo  Te pedira la contrasena de SFTP.
echo ================================================
echo.

"C:\Users\desoft\AppData\Local\Programs\WinSCP\WinSCP.com" /ini=nul /script="C:\Users\desoft\Desktop\Tortas-Del-Chiche\winscp-script.txt"

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ================================================
    echo   ARCHIVOS SUBIDOS CORRECTAMENTE
    echo   Prueba: https://lastortasdelchiche.com
    echo ================================================
) else (
    echo.
    echo ================================================
    echo   HUBO UN ERROR - Revisa la contrasena
    echo ================================================
)

echo.
pause
