import os
import time
import logging
import statistics
from math import sqrt
from datetime import datetime, timedelta

import gpsd
import config_file
import mysql.connector
from bme280 import BME280
from smbus2 import SMBus


class Datum:
    def __init__(self, timestamp, latitude, longitude, humidity):
        self.timestamp = timestamp
        self.latitude = latitude
        self.longitude = longitude
        self.humidity = humidity
        self.previous = None
        self.next = None


class DoublyLinkedList:
    date_format = "%Y-%m-%d %H:%M:%S"

    def __init__(self):
        self.head = None
        self.tail = None
        self.count = 0

    @staticmethod
    def get_time_difference(datum1, datum2):
        time_difference = datetime.strptime(datum2.timestamp, DoublyLinkedList.date_format) - datetime.strptime(
            datum1.timestamp, DoublyLinkedList.date_format)

        return time_difference

    @staticmethod
    def euclidean_distance(datum1, datum2):
        distance = sqrt((datum1.latitude - datum2.latitude) ** 2 + (datum1.longitude - datum2.longitude) ** 2)

        return distance

    def append(self, datum):
        if not self.head:
            self.head = datum
            self.tail = datum
        else:
            datum.previous = self.tail
            self.tail.next = datum
            self.tail = datum

        self.count += 1

    def append_filtered(self, datum, distance_threshold=0.00006, time_threshold=timedelta(seconds=20)):
        if self.tail is None:
            self.append(datum)
        else:
            time_difference = self.get_time_difference(self.tail, datum)
            distance = self.euclidean_distance(datum, self.tail)

            if distance >= distance_threshold and time_difference >= time_threshold:
                self.append(datum)

    def remove(self, datum):
        if datum is None:
            return

        if datum.previous:
            datum.previous.next = datum.next
        else:
            self.head = datum.next

        if datum.next:
            datum.next.previous = datum.previous
        else:
            self.tail = datum.previous

        self.count -= 1

    def find(self, timestamp):
        current_datum = self.head

        while current_datum is not None:
            if current_datum.timestamp == timestamp:
                return current_datum

            current_datum = current_datum.next

        return None

    def find_and_remove(self, timestamp):
        datum = self.find(timestamp)

        if datum:
            self.remove(datum)
            return True

        return False

    def get_datum(self, index):
        current_datum = self.head
        count = 0

        while current_datum is not None and count < index:
            current_datum = current_datum.next
            count += 1

        if current_datum is None:
            return None

        return current_datum

    def clear(self):
        self.head = None
        self.tail = None
        self.count = 0

    def print_data(self):
        current = self.head

        while current:
            message = f"{current.timestamp} Latitude: {current.latitude:.6f} Longitude: {current.longitude:.6f} Humidity: {current.humidity:.2f} %"
            print(message)
            current = current.next

    def filter(self, distance_threshold=0.00006):
        # It filters the list by removing any nodes whose distance lies within the given threshold.
        current_datum = self.head

        while current_datum is not None:
            next_datum = current_datum.next

            while next_datum is not None:
                distance = self.euclidean_distance(current_datum, next_datum)

                if distance <= distance_threshold:
                    self.remove(next_datum)

                next_datum = next_datum.next

            current_datum = current_datum.next

    def smoothing(self, window):
        if self.count == 0:
            return

        # It uses the moving average technique to distribute the data more evenly.
        humidity_averages = []
        latitude_averages = []
        longitude_averages = []

        current_datum = self.head

        while current_datum is not None:
            humidity_window = [current_datum.humidity]
            latitude_window = [current_datum.latitude]
            longitude_window = [current_datum.longitude]

            previous_datum = current_datum.previous

            for index in range(window - 1):
                if previous_datum is not None:
                    humidity_window.insert(0, previous_datum.humidity)
                    latitude_window.insert(0, previous_datum.latitude)
                    longitude_window.insert(0, previous_datum.longitude)
                    prev_datum = prev_datum.previous

            humidity_average = sum(humidity_window) / len(humidity_window)
            latitude_average = sum(latitude_window) / len(latitude_window)
            longitude_average = sum(longitude_window) / len(longitude_window)

            humidity_averages.append(humidity_average)
            latitude_averages.append(latitude_average)
            longitude_averages.append(longitude_average)

            current_datum = current_datum.next

        current_datum = self.head

        for index in range(len(humidity_averages)):
            current_datum.humidity = humidity_averages[index]
            current_datum.latitude = latitude_averages[index]
            current_datum.longitude = longitude_averages[index]
            current_datum = current_datum.next


class Dewmeter:
    _instance = None

    # Singleton pattern
    def __new__(cls):
        if cls._instance is None:
            cls._instance = super(Dewmeter, cls).__new__(cls)

        return cls._instance

    def __init__(self, cooldown_threshold=1, satellite_threshold=6, log_file_name="dewmeter.log", smooth_factor=50,
                 data_threshold=1_000_000):
        self.bus = None
        self.bme280 = None
        self.cooldown_threshold = cooldown_threshold
        self.satellite_threshold = satellite_threshold
        self.log_file_name = log_file_name
        self.smooth_factor = smooth_factor
        # Hardcoded to avoid wasting time during testing
        self.warmed_up = True
        self.database = None
        self.data_threshold = data_threshold
        self.data = DoublyLinkedList()

    def run_dewmeter(self):
        try:
            # Configure the enviro+ sensors
            self.bus = SMBus(1)
            self.bme280 = BME280(i2c_dev=self.bus)

            # Configure the GPS daemon service
            gpsd.connect()

            # Remove the old log file if it exists
            self.delete_log()

            # Configure the logger
            logging.basicConfig(filename=self.log_file_name, level=logging.INFO)
        except Exception as exception:
            print_exception(exception)
            return False

        return True

    def get_mode(self):
        packet = gpsd.get_current()
        return packet.mode

    def get_satellites(self):
        packet = gpsd.get_current()
        return packet.sats

    def get_latitude(self):
        packet = gpsd.get_current()
        return packet.lat

    def get_longitude(self):
        packet = gpsd.get_current()
        return packet.lon

    def get_humidity(self):
        return self.bme280.get_humidity()

    def collect_data(self):
        # Mode is used determine the stage of GPS receiver
        mode = self.get_mode()
        now = datetime.now()
        timestamp = now.strftime(self.data.date_format)

        if mode < 2:
            message = f"{timestamp} No GPS fix"
            print(message)
        else:
            satellites = self.get_satellites()

            if satellites < self.satellite_threshold:
                message = f"{timestamp} Not enough satellites available: {satellites}"
                print(message)
            else:
                # Calculate the median value hoping to avoid GPS drifting(outlier values)
                latitude_batch = []
                longitude_batch = []
                humidity_batch = []

                for datum in range(self.smooth_factor):
                    latitude_batch.append(self.get_latitude())
                    longitude_batch.append(self.get_longitude())
                    humidity_batch.append(self.get_humidity())

                m_latitude = statistics.median(latitude_batch)
                m_longitude = statistics.median(longitude_batch)
                m_humidity = statistics.median(humidity_batch)

                try:
                    # Add the new datum
                    datum = Datum(timestamp, m_latitude, m_longitude, m_humidity)
                    self.data.append_filtered(datum)
                    if self.data.count >= self.data_threshold:
                        # TODO: Handle the exception and give the option to free the memory
                        message = f"Buffer bounds reached. Remove some data to add new data."
                        raise Exception(message)
                    else:
                        # self.data.append(datum)
                        self.data.append_filtered(datum)

                    # Display stuff
                    message = f"{timestamp} Latitude: {m_latitude:.6f} Longitude: {m_longitude:.6f} Humidity: {m_humidity:.2f} %"
                    print(message)
                    logging.info(message)
                except Exception as exception:
                    print_exception(exception)

    def delete_log(self):
        if os.path.exists(self.log_file_name):
            os.remove(self.log_file_name)

    def filter_data(self):
        self.data.filter(0.000052)
        self.data.smoothing(4)
        self.data.filter(0.00004)

    def send_to_database(self):
        try:
            self.database = mysql.connector.connect(host=config_file.MYSQL_HOSTNAME,
                                                    port=config_file.MYSQL_PORT,
                                                    user=config_file.MYSQL_USERNAME,
                                                    password=config_file.MYSQL_PASSWORD,
                                                    database=config_file.MYSQL_DATABASE,
                                                    auth_plugin='mysql_native_password')
            print(f"Connection successful!")

            # Insert data
            for index in range(self.data.count):
                datum = self.data.get_datum(index)
                query = f"INSERT INTO sensor_data (time, latitude, longitude, humidity) VALUES ('{datum.timestamp}', '{datum.latitude}', '{datum.longitude}', '{datum.humidity}')"
                cursor = self.database.cursor()
                cursor.execute(query)
                self.database.commit()
                message = f"{datum.timestamp} Latitude: {datum.latitude} Longitude: {datum.longitude} Humidity: {datum.humidity} %"
                print(message)
                time.sleep(0.5)

            # Free the resource:
            self.data.clear()
            # Disconnect from the database
            if self.database.is_connected():
                self.database.close()
        except mysql.connector.Error as exception:
            self.database.cursor.rollback()
            print_exception(exception)
        except Exception as exception:
            print_exception(exception)

    def warm_up_sensors(self):
        if not self.warmed_up:
            os.system("clear")
            message = f"Warming up sensors..."
            print(message)

            # Based on the enviro+ documentation sensors need ~10m to stabilize
            # TODO: Make a humidity/time graph to show it as it gets more sable
            for i in range(0, 1_000_000):
                try:
                    latitude = self.get_latitude()
                    longitude = self.get_longitude()
                    humidity = self.get_humidity()
                except Exception as exception:
                    print_exception(exception)
            self.warmed_up = True
            return True
        else:
            return True
