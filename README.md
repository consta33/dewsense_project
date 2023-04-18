<img src="dewsense-logo.png" alt="DewSense" width="300px"/><br>

## Project Description :

Dewsense is a humidity tracker that uses a Raspberry Pi, humidity sensor, GPS receiver, and a remote MySQL database to collect and store real-time GPS coordinates and humidity levels. The primary objective of the project is to combine location-based data with humidity levels to provide a broad view of the environmental conditions. To achieve this, the system is equipped with a 4G USB dongle to ensure data connectivity, enabling data transmission from anywhere with 4G coverage. A continuously running python script collects and then transmits data to the remote MySQL database. The project includes a web-based application that requires user authentication through a login page. Once authenticated, the user is directed to a dashboard. The application uses the Mapbox API for data visualization, presenting a heatmap view of the collected data by selecting the date.

The application is hosted here: https://dew-sense.com/

## Hardware requirements :

- Raspberry Pi 4 8GB RAM
- S2PI UPS EP-0135 UPS HAT
- USB-UART serial converter
- Beitian BN-880 GPS receiver module
- Enviro+ sensor HAT
- Huawei E3372h-153 4G LTE USB dongle
- Power bank (needed to prolong the usage period)

## Software requirements (Linux-Python-MySQL dependencies) :

### Setting up Raspbian OS :

```bash
sudo apt update
sudo apt full-upgrade
sudo apt clean
```

### Setting up a different network-manager that is compatible with Huawei E3372h dongle :

```bash
sudo apt install network-manager network-manager-gnome
sudo apt purge openresolv dhcpcd5
sudo systemctl enable NetworkManager
sudo systemctl start NetworkManager
sudo systemctl status NetworkManager
sudo nm-applet
```

### Setting up the Enviro+ sensor HAT :

```bash
git clone https://github.com/pimoroni/enviroplus-python
cd enviroplus-python
sudo ./install.sh
```

### Setting up the Beitian BN-880 GPS module :

```bash
sudo apt install gpsd gpsd-clients
pip install gpsd-py3
pip install smbus2
```

### Setting up the MySQL :

```bash
pip install mysql-connector-python
```

### Database schema SQL :

```sql
CREATE DATABASE dewsense

USE dewsense

CREATE TABLE "sensor_data" (
  "id" int(11) NOT NULL AUTO_INCREMENT,
  "time" timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  "latitude" varchar(20) NOT NULL,
  "longitude" varchar(20) NOT NULL,
  "humidity" varchar(20) NOT NULL,
  PRIMARY KEY ("id")
  UNIQUE KEY "id_UNIQUE" ("id"),
)

CREATE TABLE "user" (
  "id" int(11) NOT NULL AUTO_INCREMENT,
  "username" varchar(16) DEFAULT NULL,
  "password" varchar(255) DEFAULT NULL,
  "auth_token" varchar(64) DEFAULT NULL,
  PRIMARY KEY ("id"),
  UNIQUE KEY "id_UNIQUE" ("id"),
  UNIQUE KEY "username_UNIQUE" ("username"),
  UNIQUE KEY "auth_token_UNIQUE" ("auth_token")
)

ALTER TABLE sensor_data AUTO_INCREMENT = 1;
```
