<p align="center"><a href="https://app.sigmie.com" target="_blank"><img width="800" src="https://res.cloudinary.com/markos-nikolaos-orfanos/image/upload/c_scale,w_3050/v1593940839/Sigmie/banner_avlw7m.png"></a></p>

<p align="center">
</p>

# Sigmie App

Documentation: https://docs.sigmie/app

Application: https://app.sigmie.com

## Installation

Clone this repository, `cd` into the repository root and run:

```bash
docker-compose --project-name app_devcontainer -f .devcontainer/docker-compose.yml up --build
```

After the build process the application will be available at http://localhost:8080

## Vue

###  Click away
```
  <div v-on-clickaway="()=> $emit('away')">
  </div>
```

### Sidebar
```
        {
          text: "Playground",
          name: "playground",
          routeParams: {
            project:
              this.$page.project_id === null ? "" : this.$page.project_id,
          },
          icon: "puzzle",
          badge: {
            text: "Soon",
            color: "blue",
          },
        }
```


### Factory
```
    $user = factory(Subscription::class)->create()->billable;
    $project = factory(Project::class)->create(['user_id' => $user->id]);
    $cluster = factory(Cluster::class)->create(['project_id' => $project->id]);
```