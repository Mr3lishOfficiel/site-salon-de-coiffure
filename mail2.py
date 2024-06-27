#!/usr/bin/env python3

import sys
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText

# Récupération des arguments de la ligne de commande
name = sys.argv[1]
email = sys.argv[2]
body = sys.argv[3]

# Informations de connexion Gmail
gmail_user = 'gsdp78@gmail.com'
gmail_password = 'fwuh owij niel ywgf'

# Informations sur l'email
from_email = gmail_user
to_email = email
bcc_email = 'clementproxys@gmail.com'
subject = 'Formulaire de contact BarberSHOP'
body = f"Nom du demandeur d'information: {name}\nEmail: {email}\n\nMessage:\n{body}"

# Création de l'objet de message
msg = MIMEMultipart()
msg['From'] = from_email
msg['To'] = to_email
msg['Subject'] = subject
msg.attach(MIMEText(body, 'plain'))

recipients = [to_email] + [bcc_email]

try:
    # Connexion au serveur SMTP de Gmail
    server = smtplib.SMTP('smtp.gmail.com', 587)
    server.starttls()  # Sécuriser la connexion
    server.login(gmail_user, gmail_password)

    # Envoi de l'email
    server.sendmail(from_email, recipients, msg.as_string())
    server.quit()

    print('Email envoyé avec succès')
except Exception as e:
    print(f'Erreur lors de l\'envoi de l\'email: {e}')
