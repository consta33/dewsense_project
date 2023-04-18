#!/usr/bin/python
import os
import time
import getch
from dewmeter import Dewmeter


def print_menu():
    os.system("clear")
    menu = """ ____  ____  _    _  __  __  ____  ____  ____  ____
(  _ \\( ___)( \\/\\/ )(  \\/  )( ___)(_  _)( ___)(  _ \\
 )(_) ))__)  )    (  )    (  )__)   )(   )__)  )   /
(____/(____)(__/\\__)(_/\\/\\_)(____) (__) (____)(_)\_)
    
DewMeter is a tool designed to collect GPS coordinates and humidity levels.
The collected data are transmitted to a remote MySQL database for storage.
    
Developed as part of the Dewsense Project (FYP)
Constantinos Baev 2023
    
Please select an option:
    
1)Start data collection
2)Send collected data to database
3)Exit"""
    print(menu)


def show_cancel_option():
    print("\nPress Ctrl+C to exit!")
    time.sleep(0.4)
    clear_screen()


def clear_screen():
    os.system("clear")


def print_exception(exception):
    message = f"Exception: {exception}"
    print(message)
    getch.getch()


def print_invalid():
    clear_screen()
    print("Invalid input, Please type again.")
    time.sleep(1)
    clear_screen()


def exit_prompt():
    clear_screen()
    global RUN

    while True:
        print("Do you want to exit? (Y/N)")
        user_input = getch.getch()

        if user_input.lower() == "y":
            RUN = False
            return True
        elif user_input.lower() == "n":
            return False
        else:
            print_invalid()


def run_menu_option(node, user_input):
    global RUN

    try:
        if user_input == "1":
            show_cancel_option()

            while RUN:
                try:
                    node.collect_data()
                    time.sleep(node.cooldown_threshold)
                except Exception as exception:
                    print_exception(exception)
                    if not exit_prompt():
                        return
        elif user_input == "2":
            show_cancel_option()

            if node.data.count > 0:
                # Filter
                node.filter_data()
                # Send to database
                node.send_to_database()
            else:
                print("No data to send.")
                getch.getch()
        elif user_input == "3":
            RUN = False
        else:
            print_invalid()
    except KeyboardInterrupt:
        exit_prompt()
    except Exception as exception:
        print_exception(exception)
        exit_prompt()


def main():
    global RUN
    node = Dewmeter()

    if not (node.run_dewmeter() and node.warm_up_sensors()):
        RUN = False

    while RUN:
        try:
            print_menu()
            user_input = getch.getch()
            run_menu_option(node, user_input)
        except KeyboardInterrupt:
            exit_prompt()
        except Exception as exception:
            print_exception(exception)

    clear_screen()
    print("Exiting...")
    node.delete_log()
    time.sleep(1)
    return 0


if __name__ == "__main__":
    clear_screen()
    RUN = True
    # Hide cursor
    print("\033[?25l", end="")
    main()
    # Show cursor
    print("\033[?25h", end="")
    clear_screen()


