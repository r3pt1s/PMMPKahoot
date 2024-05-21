# Features
- **Custom / User made** game templates

# Commands
| Usage        | Description             | Permission    |
|--------------|-------------------------|---------------|
| /kahoot      | The main Kahoot command | No Permission |
| /kahootleave | Leave your current game | No Permission |

# Configuration
```yml
public-lobbies:
  # permissionToCreate: none
  permissionToCreate: kahoot.game.public_lobby # Type "none" if your players don't need a permission to create public Kahoot games.

create-templates:
  # permissionToCreate: none
  permissionToCreate: kahoot.template.create # Type "none" if your players don't need a permission to create public Kahoot games.
```