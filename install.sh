#!/usr/bin/env bash

export RAH_API=###RAH_API###
RAH_AVAILALBE=###RAH_AVAILALBE###


# Check if rah is already installed
if command -v rah &> /dev/null
then
    echo "rah is already installed"
    # Check if current version matches the available version
    if rah version-check "$RAH_AVAILALBE" &> /dev/null; then
        echo "rah is already at the latest version $RAH_AVAILALBE"
        echo "Only setting RAH_API=$RAH_API"
        return 0
    else
        echo "your version is older"
        echo "Updating rah to version $RAH_AVAILALBE"
    fi
fi

if [ ! -d ~/bin ]; then
  echo "creating ~/bin"
  mkdir -p ~/bin
fi

if [[ ":$PATH:" != *":$HOME/bin:"* ]]; then
  echo "Adding ~/bin to PATH"
  export PATH=~/bin:$PATH
  if [ -f ~/.bashrc ]; then
    echo 'export PATH=~/bin:$PATH' >> ~/.bashrc
  elif [ -f ~/.profile ]; then
    echo 'export PATH=~/bin:$PATH' >> ~/.profile
  elif [ -f ~/.bash_profile ]; then
    echo 'export PATH=~/bin:$PATH' >> ~/.bash_profile
  elif [ -f ~/.zshrc ]; then
    echo 'export PATH=~/bin:$PATH' >> ~/.zshrc
  else
    colorRed="\033[0;31m"
    colorReset="\033[0m"
    echo "$colorRed No profile file found to add ~/bin to PATH $colorReset"
  fi
fi


curl -o ~/bin/rah -SL --fail $RAH_API/rah || {
    echo "Failed to download $RAH_API/rah"
    return 1
}
chmod +x ~/bin/rah
